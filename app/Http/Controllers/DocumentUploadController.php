<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\DocumentOrder;
use App\Services\WhatsAppService;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
     * Upload file dengan Google Drive + fallback local - FIXED
     */
    private function uploadFile($file, $serviceType)
    {
        Log::info('=== STARTING FILE UPLOAD ===', [
            'service_type' => $serviceType,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize()
        ]);

        // Coba upload ke Google Drive dulu
        $googleResult = $this->googleDriveService->uploadFile($file, $serviceType);

        Log::info('Google Drive upload result:', $googleResult);

        if ($googleResult['success']) {
            Log::info('✅ File uploaded to Google Drive successfully', [
                'file_id' => $googleResult['file_id'],
                'service_type' => $serviceType,
                'filename' => $googleResult['name']
            ]);

            // PERBAIKAN: Return data yang benar
            return [
                'success' => true,
                'storage_type' => 'google_drive',
                'path' => $googleResult['file_id'], // Store file_id as path
                'google_drive_file_id' => $googleResult['file_id'],
                'google_drive_view_url' => $googleResult['view_url'],
                'google_drive_preview_url' => $googleResult['preview_url'],
                'google_drive_download_url' => $googleResult['download_url'],
                'google_drive_direct_link' => $googleResult['direct_link'],
                'google_drive_thumbnail_url' => $googleResult['thumbnail_url'] ?? null,
                'is_google_drive' => true // PASTIKAN TRUE
            ];
        }

        // Fallback ke local storage jika Google Drive gagal
        Log::warning('⚠️ Google Drive upload failed, using local storage fallback', [
            'error' => $googleResult['error'] ?? 'Unknown error',
            'service_type' => $serviceType
        ]);

        try {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("documents/{$serviceType}", $filename, 'public');

            Log::info('📁 File uploaded to local storage (fallback)', [
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
                'is_google_drive' => false,
                'local_url' => Storage::url($path)
            ];
        } catch (\Exception $e) {
            Log::error('❌ Both Google Drive and local storage failed', [
                'google_error' => $googleResult['error'] ?? 'Unknown error',
                'local_error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Upload gagal ke Google Drive dan local storage: ' . $e->getMessage()
            ];
        }
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
            Log::error('Repair form error: ' . $e->getMessage());
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
            Log::error('Format form error: ' . $e->getMessage());
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
                Log::warning('Product not found, creating fallback');
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
            Log::error('Plagiarism form error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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

            // Upload file using Google Drive
            $uploadResult = $this->uploadFile($request->file('document'), 'repair');

            if (!$uploadResult['success']) {
                return back()->with('error', 'Gagal upload file: ' . $uploadResult['error']);
            }

            Log::info('=== CREATING ORDER WITH UPLOAD RESULT ===', $uploadResult);

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
                'is_google_drive' => $uploadResult['is_google_drive'] ? 1 : 0, // EXPLICIT CAST
                'storage_type' => $uploadResult['storage_type'],
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            Log::info('🔧 Repair order created', [
                'order_number' => $order->order_number,
                'storage_type' => $uploadResult['storage_type'],
                'is_google_drive' => $uploadResult['is_google_drive'],
                'google_drive_file_id' => $uploadResult['google_drive_file_id']
            ]);

            // Send WhatsApp notification
            try {
                $this->whatsapp()->sendOrderConfirmation($order);
            } catch (\Exception $e) {
                Log::error('Failed to send WhatsApp notification: ' . $e->getMessage());
            }

            return redirect()->route('payment.show', $order->order_number);
        } catch (\Exception $e) {
            Log::error('Repair submit error: ' . $e->getMessage());
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

            // Upload file using Google Drive
            $uploadResult = $this->uploadFile($request->file('document'), 'format');

            if (!$uploadResult['success']) {
                return back()->with('error', 'Gagal upload file: ' . $uploadResult['error']);
            }

            Log::info('=== CREATING ORDER WITH UPLOAD RESULT ===', $uploadResult);

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
                'is_google_drive' => $uploadResult['is_google_drive'] ? 1 : 0, // EXPLICIT CAST
                'storage_type' => $uploadResult['storage_type'],
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            // Send WhatsApp notification
            try {
                $this->whatsapp()->sendOrderConfirmation($order);
            } catch (\Exception $e) {
                Log::error('Failed to send WhatsApp notification: ' . $e->getMessage());
            }

            return redirect()->route('payment.show', $order->order_number);
        } catch (\Exception $e) {
            Log::error('Format submit error: ' . $e->getMessage());
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

            // Upload file using Google Drive
            $uploadResult = $this->uploadFile($request->file('document'), 'plagiarism');

            if (!$uploadResult['success']) {
                return back()->with('error', 'Gagal upload file: ' . $uploadResult['error']);
            }

            Log::info('=== CREATING PLAGIARISM ORDER WITH UPLOAD RESULT ===', $uploadResult);

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
                'is_google_drive' => $uploadResult['is_google_drive'] ? 1 : 0, // EXPLICIT CAST
                'storage_type' => $uploadResult['storage_type'],
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            Log::info('🔍 Plagiarism order created with correct Google Drive data', [
                'order_number' => $order->order_number,
                'storage_type' => $order->storage_type,
                'is_google_drive' => $order->is_google_drive,
                'google_drive_file_id' => $order->google_drive_file_id,
                'google_drive_view_url' => $order->google_drive_view_url
            ]);

            // Send WhatsApp notification
            try {
                $storageInfo = $uploadResult['is_google_drive']
                    ? "☁️ Dokumen tersimpan aman di Google Drive cloud storage"
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
                Log::error('Failed to send WhatsApp notification: ' . $e->getMessage());
            }

            return redirect()->route('payment.show', $order->order_number);
        } catch (\Exception $e) {
            Log::error('Plagiarism submit error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Keep existing WhatsApp methods
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

            Log::info('WABLAS Response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("WABLAS API Error: " . $e->getMessage());
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

            Log::info('FONNTE Response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("FONNTE API Error: " . $e->getMessage());
            return false;
        }
    }
}
