<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $token;
    private $baseUrl;

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
        $this->baseUrl = config('services.fonnte.url');
    }

    /**
     * Send WhatsApp message
     */
    public function sendMessage($phone, $message)
    {
        if (!$this->token) {
            Log::error('FONNTE API token not configured');
            return false;
        }

        $formattedPhone = $this->formatPhoneNumber($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token
            ])->post($this->baseUrl . 'send', [
                'target' => $formattedPhone,
                'message' => $message,
                'delay' => '2-5',
                'countryCode' => '62',
            ]);

            Log::info('FONNTE Response:', [
                'phone' => $formattedPhone,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("FONNTE API Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order confirmation message
     */
    public function sendOrderConfirmation($order)
    {
        $serviceEmojis = [
            'repair' => '🔧',
            'plagiarism' => '🔍',
            'format' => '📝'
        ];

        $emoji = $serviceEmojis[$order->service_type] ?? '📄';

        $message = "Terima kasih telah menggunakan layanan kami!\n\n" .
            "{$emoji} Detail Pesanan:\n" .
            "No. Order: #{$order->order_number}\n" .
            "Layanan: {$order->service_name}\n" .
            "Harga: {$order->formatted_price}\n\n" .
            "💰 Status: Menunggu pembayaran\n\n" .
            "Silakan lakukan pembayaran sesuai instruksi. Setelah pembayaran dikonfirmasi, kami akan segera memproses dokumen Anda dalam 1-2 hari kerja.\n\n" .
            "Terima kasih! 🙏";

        return $this->sendMessage($order->customer_phone, $message);
    }

    /**
     * Send payment confirmation message
     */
    public function sendPaymentConfirmation($order)
    {
        $message = "✅ Pembayaran Dikonfirmasi!\n\n" .
            "No. Order: #{$order->order_number}\n" .
            "Layanan: {$order->service_name}\n" .
            "Status: Sedang diproses\n\n" .
            "Dokumen Anda sedang dalam proses pengerjaan. Kami akan mengirimkan hasilnya maksimal dalam 2 hari kerja.\n\n" .
            "Terima kasih atas kepercayaan Anda! 🙏";

        return $this->sendMessage($order->customer_phone, $message);
    }

    /**
     * Send completion message
     */
    public function sendCompletionMessage($order, $downloadLink = null)
    {
        $message = "🎉 Pesanan Selesai!\n\n" .
            "No. Order: #{$order->order_number}\n" .
            "Layanan: {$order->service_name}\n\n" .
            "Dokumen Anda telah selesai diproses!";

        if ($downloadLink) {
            $message .= "\n\n📎 Link Download: {$downloadLink}";
        }

        $message .= "\n\nTerima kasih telah menggunakan layanan kami! Jika ada pertanyaan, silakan hubungi kami. 😊";

        return $this->sendMessage($order->customer_phone, $message);
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (preg_match('/^0/', $phone)) {
            return preg_replace('/^0/', '62', $phone);
        }

        if (!preg_match('/^62/', $phone)) {
            return '62' . $phone;
        }

        return $phone;
    }
}
