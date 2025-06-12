<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentOrder;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected $whatsappService;

    public function __construct()
    {
        $this->whatsappService = $this->getWhatsAppService();
    }

    protected function getWhatsAppService()
    {
        try {
            return app(WhatsAppService::class);
        } catch (\Exception $e) {
            return new WhatsAppService();
        }
    }

    public function handleIncoming(Request $request)
    {
        try {
            if ($request->isMethod('GET')) {
                return response()->json([
                    'status' => 'webhook_active',
                    'message' => 'WhatsApp webhook is ready',
                    'timestamp' => now()->toDateTimeString(),
                    'server' => 'Zentera Digital'
                ]);
            }

            // Enhanced logging for debugging
            Log::info('WhatsApp Webhook Received:', [
                'method' => $request->method(),
                'data' => $request->all(),
                'raw_input' => $request->getContent(),
                'headers' => $request->headers->all()
            ]);

            $data = $request->all();

            // Handle different FONNTE webhook formats with better validation
            $message = null;
            $sender = null;

            // Try multiple formats
            if (isset($data['pesan']) && isset($data['pengirim'])) {
                $message = $data['pesan'];
                $sender = $data['pengirim'];
                Log::info('Using FONNTE format (pesan/pengirim)');
            } elseif (isset($data['message']) && isset($data['sender'])) {
                $message = $data['message'];
                $sender = $data['sender'];
                Log::info('Using standard format (message/sender)');
            } elseif (isset($data['text']) && isset($data['sender'])) {
                $message = $data['text'];
                $sender = $data['sender'];
                Log::info('Using alternative format (text/sender)');
            }

            // Validate webhook data
            if (!$message || !$sender) {
                Log::warning('Invalid webhook data - no message or sender found', [
                    'available_keys' => array_keys($data),
                    'data' => $data
                ]);
                return response()->json(['status' => 'invalid_data'], 400);
            }

            // Clean phone number
            $phone = str_replace(['@s.whatsapp.net', '@c.us'], '', $sender);
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Enhanced logging for phone processing
            Log::info('Phone number processing:', [
                'original_sender' => $sender,
                'cleaned_phone' => $phone
            ]);

            // Skip if message is from our own bot or if it's a group
            if ((isset($data['fromMe']) && $data['fromMe']) ||
                (isset($data['isgroup']) && $data['isgroup'])
            ) {
                Log::info('Skipping message: fromMe or isGroup');
                return response()->json(['status' => 'ignored_message']);
            }

            // Process the message
            $result = $this->processIncomingMessage($phone, $message);

            return response()->json(['status' => 'success', 'result' => $result])
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Cache-Control', 'no-cache');
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    protected function processIncomingMessage($phone, $message)
    {
        Log::info('Processing message from: ' . $phone, [
            'message_length' => strlen($message),
            'message_preview' => substr($message, 0, 200)
        ]);

        // Enhanced order number extraction
        $orderNumber = $this->extractOrderNumber($message);

        Log::info('Order extraction result:', [
            'extracted_order' => $orderNumber,
            'phone' => $phone
        ]);

        if ($orderNumber) {
            return $this->handleOrderRelatedMessage($phone, $orderNumber, $message);
        } else {
            return $this->handleGeneralMessage($phone, $message);
        }
    }

    protected function extractOrderNumber($message)
    {
        // Enhanced regex patterns for order number extraction
        $patterns = [
            '/Order:\s*(DOC\d{8}\d{3})/i',     // "Order: DOC20250612004"
            '/(DOC\d{8}\d{3})/',               // Direct match "DOC20250612004"
            '/🔢\s*Order:\s*(DOC\d{8}\d{3})/i', // With emoji
            '/No\.?\s*Order:\s*(DOC\d{8}\d{3})/i', // "No. Order:"
            '/Order\s*ID:\s*(DOC\d{8}\d{3})/i', // "Order ID:"
            '/Order\s*Number:\s*(DOC\d{8}\d{3})/i' // "Order Number:"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                Log::info('Order number found with pattern: ' . $pattern, [
                    'match' => $matches[1]
                ]);
                return $matches[1];
            }
        }

        Log::info('No order number found in message');
        return null;
    }

    protected function handleOrderRelatedMessage($phone, $orderNumber, $message)
    {
        Log::info('Handling order-related message:', [
            'phone' => $phone,
            'order_number' => $orderNumber
        ]);

        // Find the order with enhanced logging
        $order = DocumentOrder::where('order_number', $orderNumber)->first();

        if (!$order) {
            Log::warning('Order not found in database:', [
                'order_number' => $orderNumber,
                'phone' => $phone
            ]);

            // Check if there are any orders at all
            $totalOrders = DocumentOrder::count();
            $recentOrders = DocumentOrder::latest()->take(5)->pluck('order_number');

            Log::info('Database check:', [
                'total_orders' => $totalOrders,
                'recent_orders' => $recentOrders->toArray()
            ]);

            $this->sendAutoReply(
                $phone,
                "❌ Maaf, nomor order {$orderNumber} tidak ditemukan dalam sistem kami.\n\n" .
                    "Mohon periksa kembali nomor order Anda atau hubungi customer service kami.\n\n" .
                    "Format order: DOC + tanggal + nomor urut\n" .
                    "Contoh: DOC20250612001\n\n" .
                    "Terima kasih! 🙏"
            );
            return false;
        }

        Log::info('Order found:', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_phone' => $order->customer_phone,
            'payment_status' => $order->payment_status
        ]);

        // Enhanced phone matching with better logging
        $customerPhone = preg_replace('/[^0-9]/', '', $order->customer_phone ?? '');
        $senderPhone = preg_replace('/[^0-9]/', '', $phone);

        Log::info('Phone comparison:', [
            'customer_phone_raw' => $order->customer_phone,
            'customer_phone_clean' => $customerPhone,
            'sender_phone_clean' => $senderPhone,
            'match' => $customerPhone === $senderPhone
        ]);

        // Detect message type and respond accordingly
        if ($this->isPaymentConfirmation($message)) {
            Log::info('Detected as payment confirmation');
            return $this->handlePaymentConfirmation($phone, $order, $message);
        } elseif ($this->isStatusInquiry($message)) {
            Log::info('Detected as status inquiry');
            return $this->handleStatusInquiry($phone, $order);
        } else {
            Log::info('Detected as general order message');
            return $this->handleGeneralOrderMessage($phone, $order, $message);
        }
    }

    protected function isPaymentConfirmation($message)
    {
        $paymentKeywords = [
            'pembayaran berhasil',
            'payment berhasil',
            'sudah bayar',
            'transfer berhasil',
            'bukti transfer',
            'sudah transfer',
            'sudah upload bukti',
            'telah melakukan pembayaran',
            'mohon segera diproses',
            'sudah diupload',
            'payment success',
            'bayar berhasil',
            '✅ saya telah melakukan pembayaran'
        ];

        $messageLower = strtolower($message);

        foreach ($paymentKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                Log::info('Payment confirmation keyword found: ' . $keyword);
                return true;
            }
        }

        return false;
    }

    protected function isStatusInquiry($message)
    {
        $statusKeywords = [
            'status order',
            'gimana progress',
            'sudah sampai mana',
            'kapan selesai',
            'update order',
            'progress pengerjaan',
            'bagaimana kabar order',
            'sudah selesai',
            'kapan jadi',
            'update status'
        ];

        $messageLower = strtolower($message);

        foreach ($statusKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function handlePaymentConfirmation($phone, $order, $message)
    {
        Log::info('Processing payment confirmation:', [
            'order_number' => $order->order_number,
            'current_status' => $order->payment_status
        ]);

        // Update order status if still pending
        if ($order->payment_status === 'pending') {
            $updated = $order->update([
                'payment_status' => 'paid',
                'paid_at' => now()
            ]);

            Log::info('Order status updated:', [
                'order_number' => $order->order_number,
                'update_success' => $updated,
                'new_status' => 'paid'
            ]);
        }

        // Send confirmation auto-reply
        $replyMessage = $this->getPaymentConfirmationReply($order);
        $result = $this->sendAutoReply($phone, $replyMessage);

        Log::info('Payment confirmation handled:', [
            'order_number' => $order->order_number,
            'customer_phone' => $phone,
            'reply_sent' => $result
        ]);

        return $result;
    }

    protected function handleStatusInquiry($phone, $order)
    {
        $replyMessage = $this->getStatusInquiryReply($order);
        return $this->sendAutoReply($phone, $replyMessage);
    }

    protected function handleGeneralOrderMessage($phone, $order, $message)
    {
        $replyMessage = "Terima kasih atas pesan Anda terkait order #{$order->order_number}.\n\n" .
            "📋 Status saat ini: " . $this->getStatusText($order->payment_status) . "\n\n" .
            "Kami akan segera memproses dan memberikan update terbaru. " .
            "Jika ada pertanyaan lebih lanjut, silakan hubungi customer service kami.\n\n" .
            "Terima kasih! 🙏";

        return $this->sendAutoReply($phone, $replyMessage);
    }

    protected function handleGeneralMessage($phone, $message)
    {
        $messageLower = strtolower($message);

        if (
            strpos($messageLower, 'halo') !== false ||
            strpos($messageLower, 'hello') !== false ||
            strpos($messageLower, 'hai') !== false ||
            strpos($messageLower, 'hi') !== false
        ) {
            $replyMessage = "Halo! Selamat datang di Zentera Digital 👋\n\n" .
                "Kami adalah layanan profesional untuk:\n" .
                "🔧 Perbaikan Dokumen\n" .
                "📝 Format & Daftar Isi\n" .
                "🔍 Cek Plagiarisme Turnitin\n\n" .
                "Jika Anda memiliki pertanyaan tentang order, silakan sertakan nomor order Anda.\n\n" .
                "Terima kasih! 😊";
        } else {
            $replyMessage = "Terima kasih atas pesan Anda! 🙏\n\n" .
                "Untuk mendapatkan bantuan yang lebih baik, mohon sertakan:\n" .
                "• Nomor order (jika ada)\n" .
                "• Detail pertanyaan Anda\n\n" .
                "Tim customer service kami akan segera membantu Anda.\n\n" .
                "Zentera Digital - Solusi Dokumen Terpercaya ✨";
        }

        return $this->sendAutoReply($phone, $replyMessage);
    }

    protected function getPaymentConfirmationReply($order)
    {
        $serviceMessages = [
            'plagiarism' => "Baik, mohon ditunggu yaa dalam proses pengecekan 🔍\n\n",
            'repair' => "Baik, dokumen Anda akan segera kami perbaiki 🔧\n\n",
            'format' => "Baik, kami akan segera memformat dokumen Anda 📝\n\n"
        ];

        $serviceMessage = $serviceMessages[$order->service_type] ?? "Baik, pesanan Anda akan segera kami proses 🚀\n\n";

        return $serviceMessage .
            "✅ **PEMBAYARAN DITERIMA**\n\n" .
            "📋 Detail Order:\n" .
            "🔢 No. Order: #{$order->order_number}\n" .
            "🔧 Layanan: {$order->service_name}\n" .
            "📊 Status: Sedang diproses\n\n" .
            "⏰ Estimasi: 1-2 hari kerja\n\n" .
            "Kami akan mengirimkan hasilnya via WhatsApp setelah selesai.\n\n" .
            "Terima kasih atas kepercayaan Anda! 🙏\n\n" .
            "Zentera Digital - Solusi Dokumen Terpercaya ✨";
    }

    protected function getStatusInquiryReply($order)
    {
        $statusTexts = [
            'pending' => 'Menunggu pembayaran 💰',
            'paid' => 'Sedang diproses ⏳',
            'completed' => 'Selesai ✅'
        ];

        $statusText = $statusTexts[$order->payment_status] ?? 'Unknown';

        return "📊 **STATUS ORDER**\n\n" .
            "🔢 No. Order: #{$order->order_number}\n" .
            "🔧 Layanan: {$order->service_name}\n" .
            "📊 Status: {$statusText}\n" .
            "📅 Tanggal Order: " . $order->created_at->format('d/m/Y H:i') . "\n\n" .
            ($order->payment_status === 'paid' ?
                "⏳ Pesanan Anda sedang dalam proses pengerjaan. Estimasi selesai 1-2 hari kerja.\n\n" : ($order->payment_status === 'pending' ?
                    "💰 Silakan lakukan pembayaran untuk memulai proses pengerjaan.\n\n" :
                    "✅ Pesanan Anda sudah selesai!\n\n"
                )
            ) .
            "Terima kasih! 🙏";
    }

    protected function getStatusText($status)
    {
        return [
            'pending' => 'Menunggu pembayaran',
            'paid' => 'Sedang diproses',
            'completed' => 'Selesai'
        ][$status] ?? 'Unknown';
    }

    protected function sendAutoReply($phone, $message)
    {
        try {
            Log::info('Attempting to send auto-reply:', [
                'phone' => $phone,
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 100) . '...'
            ]);

            $result = $this->whatsappService->sendMessage($phone, $message);

            Log::info('Auto-reply result:', [
                'phone' => $phone,
                'success' => $result,
                'service_class' => get_class($this->whatsappService)
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to send auto-reply:', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function testWebhook(Request $request)
    {
        try {
            // Use your actual webhook data for testing
            $testData = [
                'quick' => false,
                'device' => '6281330053572',
                'pesan' => '🎉 *PEMBAYARAN BERHASIL* 🎉

📋 *Detail Order:*
🔢 Order: DOC20250612004
👤 Nama: Muhammad Kamil
📱 Phone: 6281383894808
🔧 Layanan: Cek Plagiarisme Turnitin
💰 Total: Rp 5.000
💳 Metode: BSI
📎 File: 1749761392_LAPORAN PRAKTIKUM 03 BASIS DATA MUHAMMAD KAMIL.pdf
📝 Catatan: Hshsns

✅ Saya telah melakukan pembayaran!
📸 Bukti transfer sudah diupload

Mohon segera diproses ya. Terima kasih! 🙏',
                'pengirim' => '6281383894808',
                'message' => '🎉 *PEMBAYARAN BERHASIL* 🎉

📋 *Detail Order:*
🔢 Order: DOC20250612004
👤 Nama: Muhammad Kamil
📱 Phone: 6281383894808
🔧 Layanan: Cek Plagiarisme Turnitin
💰 Total: Rp 5.000
💳 Metode: BSI
📎 File: 1749761392_LAPORAN PRAKTIKUM 03 BASIS DATA MUHAMMAD KAMIL.pdf
📝 Catatan: Hshsns

✅ Saya telah melakukan pembayaran!
📸 Bukti transfer sudah diupload

Mohon segera diproses ya. Terima kasih! 🙏',
                'text' => 'non-button message',
                'sender' => '6281383894808',
                'name' => 'Anony_Loly',
                'type' => 'text',
                'isgroup' => false,
                'isforwarded' => false
            ];

            // Process using the same logic
            $message = $testData['pesan'];
            $sender = $testData['pengirim'];
            $phone = preg_replace('/[^0-9]/', '', $sender);

            // Test order extraction
            $orderNumber = $this->extractOrderNumber($message);
            $isPaymentConfirmation = $this->isPaymentConfirmation($message);

            // Check if order exists
            $order = null;
            if ($orderNumber) {
                $order = DocumentOrder::where('order_number', $orderNumber)->first();
            }

            // Process the message
            $result = $this->processIncomingMessage($phone, $message);

            return response()->json([
                'status' => 'success',
                'message' => 'Test webhook executed successfully',
                'debug_info' => [
                    'extracted_order' => $orderNumber,
                    'is_payment_confirmation' => $isPaymentConfirmation,
                    'processed_phone' => $phone,
                    'order_found' => $order ? true : false,
                    'order_details' => $order ? [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'payment_status' => $order->payment_status
                    ] : null,
                    'process_result' => $result
                ],
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Test webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }
}
