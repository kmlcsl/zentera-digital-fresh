<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\WhatsAppService;
use App\Models\DocumentOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminWhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * WhatsApp management dashboard
     */
    public function index()
    {
        $recentOrders = DocumentOrder::latest()->take(10)->get();

        return view('admin.whatsapp.index', compact('recentOrders'));
    }

    /**
     * Test WhatsApp connection
     */
    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $result = $this->whatsappService->sendMessage(
            $request->phone,
            $request->message
        );

        Log::info('Admin WhatsApp test', [
            'phone' => $request->phone,
            'success' => $result,
            'admin_id' => Auth::id()
        ]);

        return response()->json([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? 'Pesan berhasil dikirim' : 'Gagal mengirim pesan',
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Send broadcast message to multiple customers
     */
    public function broadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'target_type' => 'required|in:all,pending,paid,completed',
            'phones' => 'array|min:1',
            'phones.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $phones = [];

        if ($request->target_type === 'all') {
            $phones = DocumentOrder::pluck('customer_phone')->unique()->toArray();
        } elseif (in_array($request->target_type, ['pending', 'paid', 'completed'])) {
            $phones = DocumentOrder::where('payment_status', $request->target_type)
                ->pluck('customer_phone')->unique()->toArray();
        } elseif ($request->has('phones')) {
            $phones = $request->phones;
        }

        if (empty($phones)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No phone numbers found'
            ]);
        }

        $successCount = 0;
        $failedPhones = [];

        foreach ($phones as $phone) {
            $result = $this->whatsappService->sendMessage($phone, $request->message);

            if ($result) {
                $successCount++;
            } else {
                $failedPhones[] = $phone;
            }

            // Delay antar pengiriman untuk menghindari rate limit
            sleep(1);
        }

        Log::info('Admin WhatsApp broadcast', [
            'total_sent' => count($phones),
            'success_count' => $successCount,
            'failed_count' => count($failedPhones),
            'admin_id' => Auth::id()
        ]);

        return response()->json([
            'status' => $successCount > 0 ? 'success' : 'error',
            'message' => "Berhasil mengirim ke {$successCount} dari " . count($phones) . " nomor",
            'details' => [
                'total' => count($phones),
                'success' => $successCount,
                'failed' => count($failedPhones),
                'failed_phones' => $failedPhones
            ],
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Show message templates
     */
    public function templates()
    {
        $templates = [
            'order_reminder' => [
                'name' => 'Pengingat Pesanan',
                'message' => "Halo {customer_name}!\n\nIni adalah pengingat untuk pesanan Anda:\nNo. Order: #{order_number}\nLayanan: {service_name}\nStatus: {status}\n\nJika ada pertanyaan, silakan hubungi kami.\nTerima kasih! 🙏"
            ],
            'payment_reminder' => [
                'name' => 'Pengingat Pembayaran',
                'message' => "Halo {customer_name}!\n\n💰 Pengingat Pembayaran\nNo. Order: #{order_number}\nLayanan: {service_name}\nTotal: {price}\n\nSilakan lakukan pembayaran agar pesanan Anda dapat segera diproses.\n\nTerima kasih! 🙏"
            ],
            'promo' => [
                'name' => 'Promosi',
                'message' => "🎉 PROMO SPESIAL! 🎉\n\nDapatkan diskon 20% untuk semua layanan dokumen!\n\nPeriode: {start_date} - {end_date}\nKode: PROMO20\n\nJangan lewatkan kesempatan ini!\n\nInfo lebih lanjut: {contact}"
            ],
            'thank_you' => [
                'name' => 'Terima Kasih',
                'message' => "Terima kasih {customer_name}! 🙏\n\nPesanan Anda telah selesai:\nNo. Order: #{order_number}\nLayanan: {service_name}\n\nKami senang dapat melayani Anda. Jangan ragu untuk menggunakan layanan kami lagi di masa depan!\n\nSalam hangat! 😊"
            ]
        ];

        return view('admin.whatsapp.templates', compact('templates'));
    }

    /**
     * Save custom template
     */
    public function saveTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'message' => 'required|string|max:1000',
            'template_key' => 'required|string|max:50|alpha_dash'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Dalam implementasi nyata, simpan ke database atau file config
        // Untuk demo, kita log saja
        Log::info('Template saved', [
            'name' => $request->name,
            'key' => $request->template_key,
            'message' => $request->message,
            'admin_id' => Auth::id()
        ]);

        return redirect()->back()->with('success', 'Template berhasil disimpan');
    }

    /**
     * Send specific notification to order
     */
    public function sendOrderNotification(Request $request, $orderId)
    {
        $order = DocumentOrder::findOrFail($orderId);

        $type = $request->get('type', 'order');
        $customMessage = $request->get('custom_message');

        $result = false;

        if ($customMessage) {
            // Send custom message
            $result = $this->whatsappService->sendMessage($order->customer_phone, $customMessage);
        } else {
            // Send predefined message based on type
            switch ($type) {
                case 'payment':
                    $result = $this->whatsappService->sendPaymentConfirmation($order);
                    break;
                case 'completion':
                    $downloadLink = $request->get('download_link');
                    $result = $this->whatsappService->sendCompletionMessage($order, $downloadLink);
                    break;
                default:
                    $result = $this->whatsappService->sendOrderConfirmation($order);
                    break;
            }
        }

        Log::info('Admin manual WhatsApp notification', [
            'order_id' => $orderId,
            'order_number' => $order->order_number,
            'type' => $type,
            'success' => $result,
            'admin_id' => Auth::id()
        ]);

        return response()->json([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? 'Notifikasi berhasil dikirim' : 'Gagal mengirim notifikasi',
            'order_number' => $order->order_number,
            'customer' => $order->customer_name
        ]);
    }

    /**
     * Get WhatsApp statistics
     */
    public function getStats()
    {
        $stats = [
            'total_orders' => DocumentOrder::count(),
            'orders_today' => DocumentOrder::whereDate('created_at', today())->count(),
            'orders_this_week' => DocumentOrder::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'orders_this_month' => DocumentOrder::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'by_status' => [
                'pending' => DocumentOrder::where('payment_status', 'pending')->count(),
                'paid' => DocumentOrder::where('payment_status', 'paid')->count(),
                'completed' => DocumentOrder::where('payment_status', 'completed')->count()
            ],
            'by_service' => DocumentOrder::selectRaw('service_type, count(*) as count')
                ->groupBy('service_type')
                ->pluck('count', 'service_type')
                ->toArray()
        ];

        return response()->json($stats);
    }

    /**
     * Send reminder to pending payments
     */
    public function sendPaymentReminders()
    {
        $pendingOrders = DocumentOrder::where('payment_status', 'pending')
            ->where('created_at', '>=', now()->subDays(3)) // Hanya 3 hari terakhir
            ->get();

        $successCount = 0;

        foreach ($pendingOrders as $order) {
            $message = "Halo {$order->customer_name}!\n\n" .
                "💰 Pengingat Pembayaran\n" .
                "No. Order: #{$order->order_number}\n" .
                "Layanan: {$order->service_name}\n" .
                "Total: {$order->formatted_price}\n\n" .
                "Silakan lakukan pembayaran agar pesanan Anda dapat segera diproses.\n\n" .
                "Terima kasih! 🙏";

            if ($this->whatsappService->sendMessage($order->customer_phone, $message)) {
                $successCount++;
            }

            sleep(2); // Delay antar pengiriman
        }

        Log::info('Payment reminders sent', [
            'total_orders' => $pendingOrders->count(),
            'success_count' => $successCount,
            'admin_id' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pengingat berhasil dikirim ke {$successCount} dari {$pendingOrders->count()} pelanggan",
            'details' => [
                'total' => $pendingOrders->count(),
                'success' => $successCount
            ]
        ]);
    }
}
