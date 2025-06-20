<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\DocumentOrder;
use App\Services\WhatsAppService;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentUploadController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    protected function whatsapp()
    {
        try {
            return app(WhatsAppService::class);
        } catch (\Exception $e) {
            return new WhatsAppService();
        }
    }

    /**
     * Upload file dengan Google Drive + fallback local
     * FIXED: Kirim serviceType ke GoogleDriveService
     */
    private function uploadFile($file, $serviceType)
    {
        try {
            // FIXED: Upload to Google Drive dengan serviceType parameter
            $fileId = $this->googleDriveService->uploadFile($file, $serviceType);

            if ($fileId) {
                $urls = $this->googleDriveService->generateUrls($fileId);

                Log::info('File uploaded to Google Drive', [
                    'file_id' => $fileId,
                    'service_type' => $serviceType,
                    'folder_mapping' => $this->getFolderNameForService($serviceType)
                ]);

                return [
                    'success' => true,
                    'storage_type' => 'google_drive',
                    'path' => $fileId,
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
            Log::warning('Google Drive upload failed, using local fallback', [
                'error' => $e->getMessage(),
                'service_type' => $serviceType
            ]);
        }

        // Fallback to local storage
        try {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("documents/{$serviceType}", $filename, 'public');

            Log::info('File uploaded to local storage', [
                'path' => $path,
                'service_type' => $serviceType
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
            Log::error('Both Google Drive and local storage failed', [
                'error' => $e->getMessage(),
                'service_type' => $serviceType
            ]);

            return [
                'success' => false,
                'error' => 'Upload gagal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Helper method untuk debug folder mapping
     */
    private function getFolderNameForService($serviceType)
    {
        $folderMapping = [
            'repair' => 'Repair',
            'format' => 'Format',
            'plagiarism' => 'Plagiarism',
            'translation' => 'Translation',
            'proofreading' => 'Proofreading'
        ];

        return $folderMapping[$serviceType] ?? 'Others';
    }

    public function repairForm()
    {
        try {
            $product = Product::where('upload_route', 'documents.upload.repair')->first();

            if (!$product) {
                $product = Product::where('name', 'Perbaikan Dokumen')->first();
            }

            if (!$product) {
                return redirect()->route('products')->with('error', 'Layanan tidak ditemukan');
            }

            return view('documents.upload.repair', compact('product'));
        } catch (\Exception $e) {
            Log::error('Repair form error', ['error' => $e->getMessage()]);
            return redirect()->route('products')->with('error', 'Terjadi kesalahan sistem');
        }
    }

    public function formatForm()
    {
        try {
            $product = Product::where('upload_route', 'documents.upload.format')->first();

            if (!$product) {
                $product = Product::where('name', 'Daftar Isi & Format')->first();
            }

            if (!$product) {
                return redirect()->route('products')->with('error', 'Layanan tidak ditemukan');
            }

            return view('documents.upload.format', compact('product'));
        } catch (\Exception $e) {
            Log::error('Format form error', ['error' => $e->getMessage()]);
            return redirect()->route('products')->with('error', 'Terjadi kesalahan sistem');
        }
    }

    public function plagiarismForm()
    {
        try {
            $product = Product::where('upload_route', 'documents.upload.plagiarism')->first();

            if (!$product) {
                $product = Product::where('name', 'Cek Plagiarisme Turnitin')->first();
            }

            if (!$product) {
                $product = (object) [
                    'name' => 'Cek Plagiarisme Turnitin',
                    'description' => 'Layanan pengecekan plagiarisme menggunakan Turnitin',
                    'price' => 25000,
                    'formatted_price' => 'Rp 25.000',
                    'color' => 'from-red-500 to-pink-500',
                    'icon' => 'fas fa-search'
                ];
            }

            return view('documents.upload.plagiarism', compact('product'));
        } catch (\Exception $e) {
            Log::error('Plagiarism form error', ['error' => $e->getMessage()]);
            return redirect()->route('products')->with('error', 'Terjadi kesalahan sistem');
        }
    }

    public function repairSubmit(Request $request)
    {
        try {
            $request->validate([
                'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
                'name' => 'required|string|max:255',
                'phone' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $product = Product::where('name', 'Perbaikan Dokumen')->first();
            if (!$product) {
                return back()->with('error', 'Layanan tidak tersedia');
            }

            Log::info('Processing repair upload', [
                'service_type' => 'repair',
                'expected_folder' => 'Repair'
            ]);

            $uploadResult = $this->uploadFile($request->file('document'), 'repair');

            if (!$uploadResult['success']) {
                return back()->with('error', $uploadResult['error']);
            }

            $order = DocumentOrder::create([
                'order_number' => DocumentOrder::generateOrderNumber(),
                'customer_name' => $request->name,
                'customer_phone' => $request->phone,
                'service_type' => 'repair',
                'service_name' => 'Perbaikan Dokumen',
                'price' => $product->price,
                'document_path' => $uploadResult['path'],
                'google_drive_file_id' => $uploadResult['google_drive_file_id'],
                'google_drive_view_url' => $uploadResult['google_drive_view_url'],
                'google_drive_preview_url' => $uploadResult['google_drive_preview_url'],
                'google_drive_download_url' => $uploadResult['google_drive_download_url'],
                'google_drive_direct_link' => $uploadResult['google_drive_direct_link'],
                'google_drive_thumbnail_url' => $uploadResult['google_drive_thumbnail_url'],
                'is_google_drive' => $uploadResult['is_google_drive'] ? 1 : 0,
                'storage_type' => $uploadResult['storage_type'],
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            // Send WhatsApp notification
            try {
                $this->whatsapp()->sendOrderConfirmation($order);
            } catch (\Exception $e) {
                Log::error('WhatsApp notification failed', ['error' => $e->getMessage()]);
            }

            return redirect()->route('payment.show', $order->order_number);
        } catch (\Exception $e) {
            Log::error('Repair submit error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function formatSubmit(Request $request)
    {
        try {
            $request->validate([
                'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
                'name' => 'required|string|max:255',
                'phone' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $product = Product::where('name', 'Daftar Isi & Format')->first();
            if (!$product) {
                return back()->with('error', 'Layanan tidak tersedia');
            }

            Log::info('Processing format upload', [
                'service_type' => 'format',
                'expected_folder' => 'Format'
            ]);

            $uploadResult = $this->uploadFile($request->file('document'), 'format');

            if (!$uploadResult['success']) {
                return back()->with('error', $uploadResult['error']);
            }

            $order = DocumentOrder::create([
                'order_number' => DocumentOrder::generateOrderNumber(),
                'customer_name' => $request->name,
                'customer_phone' => $request->phone,
                'service_type' => 'format',
                'service_name' => 'Daftar Isi & Format',
                'price' => $product->price,
                'document_path' => $uploadResult['path'],
                'google_drive_file_id' => $uploadResult['google_drive_file_id'],
                'google_drive_view_url' => $uploadResult['google_drive_view_url'],
                'google_drive_preview_url' => $uploadResult['google_drive_preview_url'],
                'google_drive_download_url' => $uploadResult['google_drive_download_url'],
                'google_drive_direct_link' => $uploadResult['google_drive_direct_link'],
                'google_drive_thumbnail_url' => $uploadResult['google_drive_thumbnail_url'],
                'is_google_drive' => $uploadResult['is_google_drive'] ? 1 : 0,
                'storage_type' => $uploadResult['storage_type'],
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            // Send WhatsApp notification
            try {
                $this->whatsapp()->sendOrderConfirmation($order);
            } catch (\Exception $e) {
                Log::error('WhatsApp notification failed', ['error' => $e->getMessage()]);
            }

            return redirect()->route('payment.show', $order->order_number);
        } catch (\Exception $e) {
            Log::error('Format submit error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function plagiarismSubmit(Request $request)
    {
        try {
            $request->validate([
                'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
                'name' => 'required|string|max:255',
                'phone' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $product = Product::where('name', 'Cek Plagiarisme Turnitin')->first();
            $defaultPrice = $product ? $product->price : 5000;

            Log::info('Processing plagiarism upload', [
                'service_type' => 'plagiarism',
                'expected_folder' => 'Plagiarism'
            ]);

            $uploadResult = $this->uploadFile($request->file('document'), 'plagiarism');

            if (!$uploadResult['success']) {
                return back()->with('error', $uploadResult['error']);
            }

            $order = DocumentOrder::create([
                'order_number' => DocumentOrder::generateOrderNumber(),
                'customer_name' => $request->name,
                'customer_phone' => $request->phone,
                'service_type' => 'plagiarism',
                'service_name' => 'Cek Plagiarisme Turnitin',
                'price' => $defaultPrice,
                'document_path' => $uploadResult['path'],
                'google_drive_file_id' => $uploadResult['google_drive_file_id'],
                'google_drive_view_url' => $uploadResult['google_drive_view_url'],
                'google_drive_preview_url' => $uploadResult['google_drive_preview_url'],
                'google_drive_download_url' => $uploadResult['google_drive_download_url'],
                'google_drive_direct_link' => $uploadResult['google_drive_direct_link'],
                'google_drive_thumbnail_url' => $uploadResult['google_drive_thumbnail_url'],
                'is_google_drive' => $uploadResult['is_google_drive'] ? 1 : 0,
                'storage_type' => $uploadResult['storage_type'],
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            // Send WhatsApp notification
            try {
                $storageInfo = $uploadResult['is_google_drive']
                    ? "☁️ Dokumen tersimpan aman di Google Drive (Folder: Plagiarism)"
                    : "📁 Dokumen tersimpan di server";

                $message = "✅ *PESANAN DITERIMA*\n\n" .
                    "📋 Detail Pesanan:\n" .
                    "🔢 No. Order: #{$order->order_number}\n" .
                    "👤 Nama: {$order->customer_name}\n" .
                    "📱 Phone: {$order->customer_phone}\n" .
                    "🔧 Layanan: {$order->service_name}\n" .
                    "💰 Total: Rp " . number_format($order->price, 0, ',', '.') . "\n\n" .
                    "{$storageInfo}\n" .
                    "⏰ Estimasi: 1-2 hari kerja setelah pembayaran\n\n" .
                    "Silakan lakukan pembayaran untuk memproses pesanan Anda.\n\n" .
                    "Terima kasih! 🙏\n\n" .
                    "*Zentera Digital - Solusi Dokumen Terpercaya* ✨";

                $this->sendWhatsAppMessage($order->customer_phone, $message);
            } catch (\Exception $e) {
                Log::error('WhatsApp notification failed', ['error' => $e->getMessage()]);
            }

            return redirect()->route('payment.show', $order->order_number);
        } catch (\Exception $e) {
            Log::error('Plagiarism submit error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function sendWablasMessage($phone, $message)
    {
        $token = config('services.wablas.token');
        $baseUrl = config('services.wablas.base_url');

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (!str_starts_with($phone, '62')) {
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            } else if (str_starts_with($phone, '8')) {
                $phone = '62' . $phone;
            }
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/api/send-message', [
                'phone' => $phone,
                'message' => $message
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WABLAS API Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendWhatsAppMessage($phone, $message)
    {
        $token = config('services.fonnte.token');
        $url = 'https://api.fonnte.com/send';

        $phone = preg_replace('/^0/', '62', $phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => $token
            ])->post($url, [
                'target' => $phone,
                'message' => $message,
                'delay' => '2-5',
                'countryCode' => '62',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FONNTE API Error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
