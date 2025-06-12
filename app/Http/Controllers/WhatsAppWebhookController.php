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
        // Use helper method to get service
        $this->whatsappService = $this->getWhatsAppService();
    }

    /**
     * Get WhatsApp service instance
     */
    protected function getWhatsAppService()
    {
        try {
            return app(WhatsAppService::class);
        } catch (\Exception $e) {
            return new WhatsAppService();
        }
    }

    /**
     * Handle incoming WhatsApp messages from FONNTE webhook
     * Support both GET and POST methods
     */
    public function handleIncoming(Request $request)
    {
        try {
            // Handle GET request (for webhook verification)
            if ($request->isMethod('GET')) {
                return response()->json([
                    'status' => 'webhook_active',
                    'message' => 'WhatsApp webhook is ready',
                    'timestamp' => now()->toDateTimeString(),
                    'server' => 'Zentera Digital'
                ]);
            }

            // Log incoming webhook data for debugging
            Log::info('WhatsApp Webhook Received:', [
                'method' => $request->method(),
                'data' => $request->all(),
                'headers' => $request->header('User-Agent')
            ]);

            $data = $request->all();

            // Validate webhook data
            if (!isset($data['message']) || !isset($data['sender'])) {
                Log::warning('Invalid webhook data received', $data);
                return response()->json(['status' => 'invalid_data'], 400);
            }

            $message = $data['message'];
            $sender = $data['sender']; // Format: 6281383894808@s.whatsapp.net
            $phone = str_replace('@s.whatsapp.net', '', $sender);

            // Skip if message is from our own bot
            if (isset($data['fromMe']) && $data['fromMe']) {
                return response()->json(['status' => 'ignored_own_message']);
            }

            // Process the message
            $this->processIncomingMessage($phone, $message);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process incoming message and send appropriate auto-reply
     */
    protected function processIncomingMessage($phone, $message)
    {
        Log::info('Processing message from: ' . $phone, ['message' => substr($message, 0, 100) . '...']);

        // Check if message contains order number
        $orderNumber = $this->extractOrderNumber($message);

        if ($orderNumber) {
            $this->handleOrderRelatedMessage($phone, $orderNumber, $message);
        } else {
            $this->handleGeneralMessage($phone, $message);
        }
    }

    /**
     * Extract order number from message
     */
    protected function extractOrderNumber($message)
    {
        // Look for pattern: DOC followed by date and number
        if (preg_match('/DOC\d{8}\d{3}/', $message, $matches)) {
            return $matches[0];
        }

        // Look for "Order:" pattern
        if (preg_match('/Order:\s*(DOC\d{8}\d{3})/i', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Handle message with order number
     */
    protected function handleOrderRelatedMessage($phone, $orderNumber, $message)
    {
        // Find the order
        $order = DocumentOrder::where('order_number', $orderNumber)->first();

        if (!$order) {
            $this->sendAutoReply(
                $phone,
                "Maaf, nomor order {$orderNumber} tidak ditemukan. " .
                    "Mohon periksa kembali nomor order Anda atau hubungi customer service kami."
            );
            return;
        }

        // Check if customer phone matches (flexible matching)
        $customerPhone = preg_replace('/[^0-9]/', '', $order->customer_phone);
        $senderPhone = preg_replace('/[^0-9]/', '', $phone);

        if ($customerPhone !== $senderPhone) {
            Log::warning('Phone mismatch for order', [
                'order_phone' => $customerPhone,
                'sender_phone' => $senderPhone,
                'order_number' => $orderNumber
            ]);
            // Continue processing anyway - might be different device
        }

        // Detect message type and respond accordingly
        if ($this->isPaymentConfirmation($message)) {
            $this->handlePaymentConfirmation($phone, $order, $message);
        } elseif ($this->isStatusInquiry($message)) {
            $this->handleStatusInquiry($phone, $order);
        } else {
            $this->handleGeneralOrderMessage($phone, $order, $message);
        }
    }

    /**
     * Check if message is payment confirmation
     */
    protected function isPaymentConfirmation($message)
    {
        $paymentKeywords = [
            'pembayaran berhasil',
            'sudah bayar',
            'transfer berhasil',
            'bukti transfer',
            'sudah transfer',
            'payment berhasil',
            'sudah upload bukti',
            'telah melakukan pembayaran',
            'mohon segera diproses'
        ];

        $messageLower = strtolower($message);

        foreach ($paymentKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if message is status inquiry
     */
    protected function isStatusInquiry($message)
    {
        $statusKeywords = [
            'status order',
            'gimana progress',
            'sudah sampai mana',
            'kapan selesai',
            'update order',
            'progress pengerjaan',
            'bagaimana kabar order'
        ];

        $messageLower = strtolower($message);

        foreach ($statusKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle payment confirmation message
     */
    protected function handlePaymentConfirmation($phone, $order, $message)
    {
        // Update order status if still pending
        if ($order->payment_status === 'pending') {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now()
            ]);
        }

        // Send confirmation auto-reply
        $replyMessage = $this->getPaymentConfirmationReply($order);
        $this->sendAutoReply($phone, $replyMessage);

        // Log the interaction
        Log::info('Payment confirmation handled', [
            'order_number' => $order->order_number,
            'customer_phone' => $phone,
            'updated_status' => 'paid'
        ]);
    }

    /**
     * Handle status inquiry
     */
    protected function handleStatusInquiry($phone, $order)
    {
        $replyMessage = $this->getStatusInquiryReply($order);
        $this->sendAutoReply($phone, $replyMessage);
    }

    /**
     * Handle general order-related message
     */
    protected function handleGeneralOrderMessage($phone, $order, $message)
    {
        $replyMessage = "Terima kasih atas pesan Anda terkait order #{$order->order_number}.\n\n" .
            "📋 Status saat ini: " . $this->getStatusText($order->payment_status) . "\n\n" .
            "Kami akan segera memproses dan memberikan update terbaru. " .
            "Jika ada pertanyaan lebih lanjut, silakan hubungi customer service kami.\n\n" .
            "Terima kasih! 🙏";

        $this->sendAutoReply($phone, $replyMessage);
    }

    /**
     * Handle general message without order number
     */
    protected function handleGeneralMessage($phone, $message)
    {
        // Check for general keywords
        $messageLower = strtolower($message);

        if (
            strpos($messageLower, 'halo') !== false ||
            strpos($messageLower, 'hello') !== false ||
            strpos($messageLower, 'hai') !== false
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

        $this->sendAutoReply($phone, $replyMessage);
    }

    /**
     * Get payment confirmation reply message
     */
    protected function getPaymentConfirmationReply($order)
    {
        $serviceMessages = [
            'plagiarism' => "Baik, mohon ditunggu yaa dalam proses pengecekkan\n\n",
            'repair' => "Baik, dokumen Anda akan segera kami perbaiki\n\n",
            'format' => "Baik, kami akan segera memformat dokumen Anda\n\n"
        ];

        $serviceMessage = $serviceMessages[$order->service_type] ?? "Baik, pesanan Anda akan segera kami proses\n\n";

        return $serviceMessage .
            "✅ **PEMBAYARAN DITERIMA**\n\n" .
            "📋 Detail Order:\n" .
            "No. Order: #{$order->order_number}\n" .
            "Layanan: {$order->service_name}\n" .
            "Status: Sedang diproses\n\n" .
            "⏰ Estimasi: 1-2 hari kerja\n\n" .
            "Kami akan mengirimkan hasilnya via WhatsApp setelah selesai.\n\n" .
            "Terima kasih atas kepercayaan Anda! 🙏";
    }

    /**
     * Get status inquiry reply message
     */
    protected function getStatusInquiryReply($order)
    {
        $statusTexts = [
            'pending' => 'Menunggu pembayaran',
            'paid' => 'Sedang diproses',
            'completed' => 'Selesai'
        ];

        $statusText = $statusTexts[$order->payment_status] ?? 'Unknown';

        return "📊 **STATUS ORDER**\n\n" .
            "No. Order: #{$order->order_number}\n" .
            "Layanan: {$order->service_name}\n" .
            "Status: {$statusText}\n" .
            "Tanggal Order: " . $order->created_at->format('d/m/Y H:i') . "\n\n" .
            ($order->payment_status === 'paid' ?
                "⏳ Pesanan Anda sedang dalam proses pengerjaan. Estimasi selesai 1-2 hari kerja.\n\n" : ($order->payment_status === 'pending' ?
                    "💰 Silakan lakukan pembayaran untuk memulai proses pengerjaan.\n\n" :
                    "✅ Pesanan Anda sudah selesai!\n\n"
                )
            ) .
            "Terima kasih! 🙏";
    }

    /**
     * Get status text
     */
    protected function getStatusText($status)
    {
        return [
            'pending' => 'Menunggu pembayaran',
            'paid' => 'Sedang diproses',
            'completed' => 'Selesai'
        ][$status] ?? 'Unknown';
    }

    /**
     * Send auto-reply message
     */
    protected function sendAutoReply($phone, $message)
    {
        try {
            $result = $this->whatsappService->sendMessage($phone, $message);

            Log::info('Auto-reply sent', [
                'phone' => $phone,
                'success' => $result,
                'message_preview' => substr($message, 0, 50) . '...'
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to send auto-reply: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test webhook functionality
     */
    public function testWebhook(Request $request)
    {
        try {
            // Simulate incoming message from customer
            $testData = [
                'message' => 'PEMBAYARAN BERHASIL
Detail Order:
Order: DOC20250612002
Nama: Muhammad Kamil
Phone: 6281383894808
Layanan: Cek Plagiarisme Turnitin
Total: Rp 5.000
Metode: BSI
File: 1749759698_Bakteri_Penyebab_Diare1[1].pdf
Catatan: GJH
Saya telah melakukan pembayaran!
Bukti transfer sudah diupload
Mohon segera diproses ya. Terima kasih!',
                'sender' => '6281383894808@s.whatsapp.net',
                'device' => 'test_device',
                'fromMe' => false
            ];

            // Process the test message
            $this->processIncomingMessage('6281383894808', $testData['message']);

            return response()->json([
                'status' => 'success',
                'message' => 'Test webhook executed successfully',
                'extracted_order' => $this->extractOrderNumber($testData['message']),
                'is_payment_confirmation' => $this->isPaymentConfirmation($testData['message']),
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Test webhook error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
