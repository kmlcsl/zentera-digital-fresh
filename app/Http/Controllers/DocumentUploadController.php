<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\DocumentOrder;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentUploadController extends Controller
{
    // Remove constructor dependency, use helper method instead

    protected function whatsapp()
    {
        try {
            return app(WhatsAppService::class);
        } catch (\Exception $e) {
            // Fallback to manual instantiation if service not registered
            return new WhatsAppService();
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

            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            $file->move(sys_get_temp_dir(), $filename);
            $path = 'documents/repair/' . $filename;

            $order = DocumentOrder::create([
                'order_number' => DocumentOrder::generateOrderNumber(),
                'customer_name' => $request->name,
                'customer_phone' => $request->phone,
                'service_type' => 'repair',
                'service_name' => 'Perbaikan Dokumen',
                'price' => $product->price,
                'document_path' => $path,
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            Log::info('Order created successfully: ' . $order->order_number);

            // Send WhatsApp notification using helper method
            try {
                $this->whatsapp()->sendOrderConfirmation($order);
            } catch (\Exception $e) {
                Log::error('Failed to send WhatsApp notification: ' . $e->getMessage());
                // Continue without failing
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

            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            $file->move(sys_get_temp_dir(), $filename);
            $path = 'documents/format/' . $filename;

            $order = DocumentOrder::create([
                'order_number' => DocumentOrder::generateOrderNumber(),
                'customer_name' => $request->name,
                'customer_phone' => $request->phone,
                'service_type' => 'format',
                'service_name' => 'Daftar Isi & Format',
                'price' => $product->price,
                'document_path' => $path,
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

    /**
     * Send WhatsApp message using Wablas API
     */
    public function sendWablasMessage($phone, $message)
    {
        $token = config('services.wablas.token'); // Tambah di config
        $baseUrl = config('services.wablas.base_url'); // Tambah di config

        // Clean phone number
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

    /**
     * Update plagiarismSubmit to use Wablas
     */
    public function plagiarismSubmit(Request $request)
    {
        try {
            // Validation
            $request->validate([
                'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
                'name' => 'required|string|max:255',
                'phone' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            // Get product and set price
            $product = Product::where('name', 'Cek Plagiarisme Turnitin')->first();

            if (!$product) {
                $defaultPrice = 25000;
                Log::warning('Product not found, using default price: ' . $defaultPrice);
            } else {
                $defaultPrice = $product->price;
            }

            // Handle file upload
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            $file->move(sys_get_temp_dir(), $filename);
            $path = 'documents/plagiarism/' . $filename;

            // Create order
            $order = DocumentOrder::create([
                'order_number' => DocumentOrder::generateOrderNumber(),
                'customer_name' => $request->name,
                'customer_phone' => $request->phone,
                'service_type' => 'plagiarism',
                'service_name' => 'Cek Plagiarisme Turnitin',
                'price' => $defaultPrice,
                'document_path' => $path,
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            Log::info('Plagiarism order created: ' . $order->order_number);

            // Send WhatsApp notification using existing FONNTE method (safer for now)
            try {
                $message = "✅ *PESANAN DITERIMA*\n\n" .
                    "📋 Detail Pesanan:\n" .
                    "🔢 No. Order: #{$order->order_number}\n" .
                    "👤 Nama: {$order->customer_name}\n" .
                    "📱 Phone: {$order->customer_phone}\n" .
                    "🔧 Layanan: {$order->service_name}\n" .
                    "💰 Total: " . number_format($order->price, 0, ',', '.') . "\n\n" .
                    "⏰ Estimasi: 1-2 hari kerja setelah pembayaran\n\n" .
                    "Silakan lakukan pembayaran untuk memproses pesanan Anda.\n\n" .
                    "Terima kasih! 🙏\n\n" .
                    "*Zentera Digital - Solusi Dokumen Terpercaya* ✨";

                // Use existing FONNTE method for now
                $this->sendWhatsAppMessage($order->customer_phone, $message);
            } catch (\Exception $e) {
                Log::error('Failed to send WhatsApp notification: ' . $e->getMessage());
                // Continue without failing
            }

            return redirect()->route('payment.show', $order->order_number);
        } catch (\Exception $e) {
            Log::error('Plagiarism submit error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Keep existing method for compatibility
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
