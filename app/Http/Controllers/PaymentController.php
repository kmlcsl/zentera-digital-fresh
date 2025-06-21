<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentOrder;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    public function show($orderNumber)
    {
        $order = DocumentOrder::where('order_number', $orderNumber)->firstOrFail();

        // Bank accounts info
        $bankAccounts = [
            'bsi' => [
                'name' => 'Bank Syariah Indonesia (BSI)',
                'account_number' => '7254348273', // Ganti dengan nomor rekening asli
                'account_name' => 'MUHAMMAD KAMIL',
                'code' => 'BSI'
            ],
            'dana' => [
                'name' => 'DANA',
                'account_number' => '081330053572', // Ganti dengan nomor DANA asli
                'account_name' => 'ZENTERA DIGITAL'
            ]
        ];

        return view('payment.show', compact('order', 'bankAccounts'));
    }

    public function confirm(Request $request, $orderNumber)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
            'payment_method' => 'required|in:bsi,dana'
        ]);

        $order = DocumentOrder::where('order_number', $orderNumber)->firstOrFail();

        try {
            // UPDATED: Upload payment proof to Google Drive + fallback local
            $uploadResult = $this->uploadPaymentProof($request->file('payment_proof'), $orderNumber);

            if (!$uploadResult['success']) {
                return back()->with('error', $uploadResult['error'])->withInput();
            }

            // Update order dengan data Google Drive
            $order->update([
                'payment_status' => 'paid',
                'payment_proof' => $uploadResult['path'],
                'payment_proof_google_drive_file_id' => $uploadResult['google_drive_file_id'],
                'payment_proof_google_drive_view_url' => $uploadResult['google_drive_view_url'],
                'payment_proof_google_drive_download_url' => $uploadResult['google_drive_download_url'],
                'payment_proof_is_google_drive' => $uploadResult['is_google_drive'] ? 1 : 0,
                'payment_proof_storage_type' => $uploadResult['storage_type'],
                'paid_at' => now()
            ]);

            Log::info('Payment confirmed successfully', [
                'order_number' => $orderNumber,
                'storage_type' => $uploadResult['storage_type'],
                'is_google_drive' => $uploadResult['is_google_drive']
            ]);

            // Generate WhatsApp message
            $paymentMethod = $request->payment_method == 'bsi' ? 'BSI' : 'DANA';
            $storageInfo = $uploadResult['is_google_drive']
                ? "☁️ Bukti transfer tersimpan aman di Google Drive"
                : "📁 Bukti transfer tersimpan di server";

            $whatsappMessage = "🎉 *PEMBAYARAN BERHASIL* 🎉\n\n" .
                "📋 *Detail Order:*\n" .
                "🔢 Order: {$order->order_number}\n" .
                "👤 Nama: {$order->customer_name}\n" .
                "📱 Phone: {$order->customer_phone}\n" .
                "🔧 Layanan: {$order->service_name}\n" .
                "💰 Total: {$order->formatted_price}\n" .
                "💳 Metode: {$paymentMethod}\n" .
                "📎 File: " . basename($order->document_path) . "\n" .
                ($order->notes ? "📝 Catatan: {$order->notes}\n" : "") .
                "\n✅ Saya telah melakukan pembayaran!\n" .
                "📸 Bukti transfer sudah diupload\n" .
                "{$storageInfo}\n\n" .
                "Mohon segera diproses ya. Terima kasih! 🙏";

            $whatsappUrl = "https://wa.me/6281330053572?text=" . urlencode($whatsappMessage);

            return redirect()->away($whatsappUrl);
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Upload payment proof dengan Google Drive + fallback local
     */
    private function uploadPaymentProof($file, $orderNumber)
    {
        try {
            // Generate filename dengan order number
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = "payment_proof_{$orderNumber}_" . time() . ".{$extension}";

            Log::info('Uploading payment proof to Google Drive', [
                'order_number' => $orderNumber,
                'original_name' => $originalName,
                'generated_filename' => $filename
            ]);

            // Upload to Google Drive dengan service type 'payment'
            $fileId = $this->googleDriveService->uploadFile($file, 'payment');

            if ($fileId) {
                $urls = $this->googleDriveService->generateUrls($fileId);

                Log::info('Payment proof uploaded to Google Drive successfully', [
                    'order_number' => $orderNumber,
                    'file_id' => $fileId,
                    'view_url' => $urls['view_url']
                ]);

                return [
                    'success' => true,
                    'storage_type' => 'google_drive',
                    'path' => $fileId, // Store file ID as path
                    'google_drive_file_id' => $fileId,
                    'google_drive_view_url' => $urls['view_url'],
                    'google_drive_preview_url' => $urls['preview_url'],
                    'google_drive_download_url' => $urls['download_url'],
                    'google_drive_direct_link' => $urls['direct_link'],
                    'google_drive_thumbnail_url' => $urls['thumbnail_url'],
                    'is_google_drive' => true
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Google Drive upload failed for payment proof, using local fallback', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to local storage
        try {
            $filename = 'payment_' . $orderNumber . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('payment_proofs', $filename, 'public');

            Log::info('Payment proof uploaded to local storage', [
                'order_number' => $orderNumber,
                'local_path' => $path
            ]);

            return [
                'success' => true,
                'storage_type' => 'local',
                'path' => $path,
                'google_drive_file_id' => null,
                'google_drive_view_url' => null,
                'google_drive_preview_url' => null,
                'google_drive_download_url' => null,
                'google_drive_direct_link' => null,
                'google_drive_thumbnail_url' => null,
                'is_google_drive' => false
            ];
        } catch (\Exception $e) {
            Log::error('Both Google Drive and local storage failed for payment proof', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Upload bukti pembayaran gagal: ' . $e->getMessage()
            ];
        }
    }
}
