<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Models\DocumentOrder;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Test basic WhatsApp functionality
     */
    public function testWhatsApp()
    {
        $result = $this->whatsappService->sendMessage(
            '6281330053572', // Nomor Zentera Digital
            'Tes integrasi FONNTE API berhasil!'
        );

        return response()->json([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? 'Pesan terkirim' : 'Gagal mengirim',
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Test order confirmation message
     */
    public function testWhatsAppOrder()
    {
        // Create dummy order object
        $order = (object) [
            'order_number' => 'DOC' . date('Ymd') . '999',
            'service_name' => 'Test Perbaikan Dokumen',
            'service_type' => 'repair',
            'formatted_price' => 'Rp 50.000',
            'customer_phone' => '6281330053572'
        ];

        $result = $this->whatsappService->sendOrderConfirmation($order);

        return response()->json([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? 'Order confirmation sent' : 'Failed to send order confirmation',
            'order_number' => $order->order_number,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Test payment confirmation message
     */
    public function testWhatsAppPayment()
    {
        $order = (object) [
            'order_number' => 'DOC' . date('Ymd') . '998',
            'service_name' => 'Test Format Dokumen',
            'service_type' => 'format',
            'formatted_price' => 'Rp 30.000',
            'customer_phone' => '6281330053572'
        ];

        $result = $this->whatsappService->sendPaymentConfirmation($order);

        return response()->json([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? 'Payment confirmation sent' : 'Failed to send payment confirmation',
            'order_number' => $order->order_number,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Test completion message
     */
    public function testWhatsAppCompletion()
    {
        $order = (object) [
            'order_number' => 'DOC' . date('Ymd') . '997',
            'service_name' => 'Test Cek Plagiarisme',
            'service_type' => 'plagiarism',
            'formatted_price' => 'Rp 25.000',
            'customer_phone' => '6281330053572'
        ];

        $downloadLink = 'https://example.com/download/hasil-plagiarisme.pdf';
        $result = $this->whatsappService->sendCompletionMessage($order, $downloadLink);

        return response()->json([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? 'Completion message sent' : 'Failed to send completion message',
            'order_number' => $order->order_number,
            'download_link' => $downloadLink,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Test with real order data
     */
    public function testWithRealOrder(Request $request)
    {
        $orderNumber = $request->get('order_number');

        if (!$orderNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order number required'
            ]);
        }

        $order = DocumentOrder::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ]);
        }

        $messageType = $request->get('type', 'order'); // order, payment, completion

        switch ($messageType) {
            case 'payment':
                $result = $this->whatsappService->sendPaymentConfirmation($order);
                $message = 'Payment confirmation';
                break;
            case 'completion':
                $downloadLink = $request->get('download_link');
                $result = $this->whatsappService->sendCompletionMessage($order, $downloadLink);
                $message = 'Completion message';
                break;
            default:
                $result = $this->whatsappService->sendOrderConfirmation($order);
                $message = 'Order confirmation';
                break;
        }

        return response()->json([
            'status' => $result ? 'success' : 'error',
            'message' => $result ? $message . ' sent successfully' : 'Failed to send ' . $message,
            'order_number' => $order->order_number,
            'customer' => $order->customer_name,
            'phone' => $order->customer_phone,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Test all message types at once
     */
    public function testAllMessages()
    {
        $phone = '6281330053572';
        $results = [];

        // Test 1: Basic message
        $result1 = $this->whatsappService->sendMessage($phone, '🧪 Test 1: Basic Message - ' . now()->format('H:i:s'));
        $results['basic'] = $result1;

        sleep(2); // Delay antar pesan

        // Test 2: Order confirmation
        $order = (object) [
            'order_number' => 'TEST' . now()->format('His'),
            'service_name' => 'Complete Test Service',
            'service_type' => 'repair',
            'formatted_price' => 'Rp 75.000',
            'customer_phone' => $phone
        ];

        $result2 = $this->whatsappService->sendOrderConfirmation($order);
        $results['order'] = $result2;

        sleep(2);

        // Test 3: Payment confirmation
        $result3 = $this->whatsappService->sendPaymentConfirmation($order);
        $results['payment'] = $result3;

        sleep(2);

        // Test 4: Completion message
        $result4 = $this->whatsappService->sendCompletionMessage($order, 'https://test.com/download.pdf');
        $results['completion'] = $result4;

        $successCount = array_sum($results);
        $totalTests = count($results);

        return response()->json([
            'status' => $successCount === $totalTests ? 'success' : 'partial',
            'message' => "Completed {$successCount}/{$totalTests} tests successfully",
            'results' => $results,
            'details' => [
                'basic_message' => $results['basic'] ? 'sent' : 'failed',
                'order_confirmation' => $results['order'] ? 'sent' : 'failed',
                'payment_confirmation' => $results['payment'] ? 'sent' : 'failed',
                'completion_message' => $results['completion'] ? 'sent' : 'failed'
            ],
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
