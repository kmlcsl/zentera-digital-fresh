<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\DocumentOrder;
use App\Services\WhatsAppService;

class AdminOrderController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display orders list
     */
    public function index()
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        try {
            // Ambil semua document orders dengan pagination
            $orders = DocumentOrder::latest()->paginate(20);

            // Statistik berdasarkan payment_status
            $stats = [
                'total_orders' => DocumentOrder::count(),
                'pending_orders' => DocumentOrder::where('payment_status', 'pending')->count(),
                'paid_orders' => DocumentOrder::where('payment_status', 'paid')->count(),
                'completed_orders' => DocumentOrder::where('payment_status', 'completed')->count(),
                'total_revenue' => DocumentOrder::where('payment_status', 'completed')->sum('price'),
            ];

            return view('admin.orders.index', compact('orders', 'stats'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memuat data pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // Services untuk document processing
        $services = [
            'repair' => ['name' => 'Perbaikan Dokumen', 'price' => 75000],
            'plagiarism' => ['name' => 'Cek Plagiarisme Turnitin', 'price' => 35000],
            'format' => ['name' => 'Daftar Isi & Format', 'price' => 50000],
        ];

        return view('admin.orders.create', compact('services'));
    }

    /**
     * Store new order - UPDATED untuk DocumentOrder
     */
    public function store(Request $request)
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'service_type' => 'required|in:repair,plagiarism,format',
            'price' => 'required|numeric|min:0',
            'document' => 'nullable|file|mimes:pdf,doc,docx,txt|max:10240',
            'notes' => 'nullable|string'
        ]);

        try {
            // Handle file upload jika ada
            $documentPath = null;
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $filename = time() . '_' . $file->getClientOriginalName();
                $documentPath = $file->storeAs('documents/admin', $filename, 'public');
            }

            // Create DocumentOrder
            $order = DocumentOrder::create([
                'order_number' => DocumentOrder::generateOrderNumber(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'service_type' => $request->service_type,
                'service_name' => $this->getServiceName($request->service_type),
                'price' => $request->price,
                'document_path' => $documentPath,
                'notes' => $request->notes,
                'payment_status' => 'pending'
            ]);

            return redirect()->route('admin.orders.index')->with('success', 'Pesanan berhasil dibuat!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show order details - UPDATED untuk DocumentOrder
     */
    public function show($id)
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        try {
            $order = DocumentOrder::findOrFail($id);
            return view('admin.orders.show', compact('order'));
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.index')->with('error', 'Pesanan tidak ditemukan!');
        }
    }

    /**
     * Show edit form - UPDATED untuk DocumentOrder
     */
    public function edit($id)
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        try {
            $order = DocumentOrder::findOrFail($id);
            return view('admin.orders.edit', compact('order'));
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.index')->with('error', 'Pesanan tidak ditemukan!');
        }
    }

    /**
     * Update order - UPDATED untuk DocumentOrder
     */
    public function update(Request $request, $id)
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'service_type' => 'required|in:repair,plagiarism,format',
            'price' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid,completed',
            'notes' => 'nullable|string'
        ]);

        try {
            $order = DocumentOrder::findOrFail($id);

            $order->update([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'service_type' => $request->service_type,
                'service_name' => $this->getServiceName($request->service_type),
                'price' => $request->price,
                'payment_status' => $request->payment_status,
                'notes' => $request->notes
            ]);

            return redirect()->route('admin.orders.index')->with('success', 'Pesanan berhasil diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengupdate pesanan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete order - UPDATED untuk DocumentOrder
     */
    public function destroy($id)
    {
        if (!Session::get('admin_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $order = DocumentOrder::findOrFail($id);
            $orderNumber = $order->order_number;

            // Delete associated files
            if ($order->document_path && Storage::disk('public')->exists($order->document_path)) {
                Storage::disk('public')->delete($order->document_path);
            }

            if ($order->payment_proof && Storage::disk('public')->exists($order->payment_proof)) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan ' . $orderNumber . ' berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus pesanan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update payment status via AJAX - NEW METHOD
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        if (!Session::get('admin_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $request->validate([
                'payment_status' => 'required|in:pending,paid,completed'
            ]);

            $order = DocumentOrder::findOrFail($id);
            $oldStatus = $order->payment_status;
            $newStatus = $request->payment_status;

            // Update payment status
            $order->payment_status = $newStatus;

            // Set paid_at timestamp when status changes to paid
            if ($newStatus === 'paid' && $oldStatus !== 'paid') {
                $order->paid_at = now();
            }

            // Clear paid_at if status changes back to pending
            if ($newStatus === 'pending') {
                $order->paid_at = null;
            }

            $order->save();

            // Generate new status badge HTML
            $statusBadge = $order->status_badge;

            Log::info('Payment status updated successfully', [
                'order_id' => $id,
                'order_number' => $order->order_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'paid_at' => $order->paid_at
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil diupdate!',
                'new_status' => $order->status_badge,
                'order' => [
                    'id' => $order->id,
                    'payment_status' => $order->payment_status,
                    'paid_at' => $order->paid_at ? $order->paid_at->format('d M Y, H:i') : null
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Data tidak valid: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating payment status', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Gagal update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details via AJAX - NEW METHOD
     */
    public function getOrderDetails($id)
    {
        if (!Session::get('admin_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $order = DocumentOrder::findOrFail($id);

            // Handle document path berdasarkan storage type
            $documentPath = null;
            if ($order->document_path) {
                if ($order->is_google_drive && $order->google_drive_view_url) {
                    $documentPath = $order->google_drive_view_url;
                } else {
                    $documentPath = Storage::url($order->document_path);
                }
            }

            // UPDATED: Handle payment proof (Google Drive or local storage)
            $paymentProof = null;
            if ($order->payment_proof || $order->payment_proof_google_drive_file_id) {
                if ($order->payment_proof_is_google_drive && $order->payment_proof_google_drive_view_url) {
                    // Google Drive payment proof
                    $paymentProof = $order->payment_proof_google_drive_view_url;
                } else if ($order->payment_proof) {
                    // Local storage payment proof
                    $paymentProof = Storage::url($order->payment_proof);
                }
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'service_name' => $order->service_name,
                    'service_type' => $order->service_type,
                    'price' => $order->formatted_price,
                    'payment_status' => $order->payment_status,
                    'notes' => $order->notes,
                    'created_at' => $order->created_at->format('d M Y, H:i'),
                    'paid_at' => $order->paid_at ? $order->paid_at->format('d M Y, H:i') : null,

                    // Storage information
                    'is_google_drive' => $order->is_google_drive ? true : false,
                    'storage_type' => $order->storage_type ?? 'local',

                    // Document paths
                    'document_path' => $documentPath,

                    // UPDATED: Payment proof paths dengan Google Drive support
                    'payment_proof' => $paymentProof,

                    // Google Drive specific fields (documents)
                    'google_drive_file_id' => $order->google_drive_file_id,
                    'google_drive_view_url' => $order->google_drive_view_url,
                    'google_drive_preview_url' => $order->google_drive_preview_url,
                    'google_drive_download_url' => $order->google_drive_download_url,
                    'google_drive_direct_link' => $order->google_drive_direct_link,
                    'google_drive_thumbnail_url' => $order->google_drive_thumbnail_url,

                    // UPDATED: Payment proof Google Drive specific fields
                    'payment_proof_google_drive_file_id' => $order->payment_proof_google_drive_file_id,
                    'payment_proof_google_drive_view_url' => $order->payment_proof_google_drive_view_url,
                    'payment_proof_google_drive_preview_url' => $order->payment_proof_google_drive_preview_url,
                    'payment_proof_google_drive_download_url' => $order->payment_proof_google_drive_download_url,
                    'payment_proof_google_drive_direct_link' => $order->payment_proof_google_drive_direct_link,
                    'payment_proof_google_drive_thumbnail_url' => $order->payment_proof_google_drive_thumbnail_url,
                    'payment_proof_is_google_drive' => $order->payment_proof_is_google_drive ? true : false,
                    'payment_proof_storage_type' => $order->payment_proof_storage_type ?? 'local'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting order details', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Gagal memuat detail order: ' . $e->getMessage()], 500);
        }
    }

    public function downloadFile($id, $type)
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        try {
            $order = DocumentOrder::findOrFail($id);

            if ($type === 'document' && $order->document_path) {
                // PERBAIKAN: Path handling yang benar
                $fullPath = storage_path('app/public/' . $order->document_path);

                Log::info('Attempting to download file', [
                    'order_id' => $id,
                    'document_path' => $order->document_path,
                    'full_path' => $fullPath,
                    'file_exists' => file_exists($fullPath)
                ]);

                if (file_exists($fullPath)) {
                    $fileName = basename($order->document_path);
                    return response()->download($fullPath, $fileName);
                } else {
                    Log::warning('File not found', [
                        'path' => $fullPath,
                        'order_id' => $id
                    ]);
                    return back()->with('error', 'File dokumen tidak ditemukan di: ' . $fullPath);
                }
            }

            if ($type === 'payment' && $order->payment_proof) {
                $fullPath = storage_path('app/public/' . $order->payment_proof);

                if (file_exists($fullPath)) {
                    $fileName = basename($order->payment_proof);
                    return response()->download($fullPath, $fileName);
                } else {
                    return back()->with('error', 'File bukti pembayaran tidak ditemukan!');
                }
            }

            return back()->with('error', 'File tidak ditemukan atau tipe file tidak valid!');
        } catch (\Exception $e) {
            Log::error('Download file error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengunduh file: ' . $e->getMessage());
        }
    }

    /**
     * Filter orders - NEW METHOD
     */
    public function filter(Request $request)
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        try {
            $query = DocumentOrder::query();

            // Filter by payment status
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by service type
            if ($request->filled('service_type')) {
                $query->where('service_type', $request->service_type);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search by customer name or order number
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            $orders = $query->latest()->paginate(20);

            // Statistik untuk filtered results
            $stats = [
                'total_orders' => $query->count(),
                'pending_orders' => (clone $query)->where('payment_status', 'pending')->count(),
                'paid_orders' => (clone $query)->where('payment_status', 'paid')->count(),
                'completed_orders' => (clone $query)->where('payment_status', 'completed')->count(),
                'total_revenue' => (clone $query)->where('payment_status', 'completed')->sum('price'),
            ];

            return view('admin.orders.index', compact('orders', 'stats'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memfilter data: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to get service name
     */
    private function getServiceName($serviceType)
    {
        $services = [
            'repair' => 'Perbaikan Dokumen',
            'plagiarism' => 'Cek Plagiarisme Turnitin',
            'format' => 'Daftar Isi & Format'
        ];

        return $services[$serviceType] ?? 'Unknown Service';
    }


    /**
     * Send payment confirmation notification
     */
    public function notifyPayment($id)
    {
        try {
            $order = DocumentOrder::findOrFail($id);

            // Update status to paid if not already
            if ($order->payment_status === 'pending') {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now()
                ]);
            }

            $result = $this->whatsappService->sendPaymentConfirmation($order);

            Log::info('Payment notification sent from admin', [
                'order_id' => $id,
                'order_number' => $order->order_number,
                'success' => $result,
                'admin_id' => Auth::id()
            ]);

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Notifikasi pembayaran berhasil dikirim'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim notifikasi pembayaran'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending payment notification: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem'
            ]);
        }
    }

    /**
     * Send completion notification
     */
    public function notifyCompletion(Request $request, $id)
    {
        try {
            $order = DocumentOrder::findOrFail($id);

            // Update status to completed
            $order->update(['payment_status' => 'completed']);

            $downloadLink = $request->get('download_link');
            $result = $this->whatsappService->sendCompletionMessage($order, $downloadLink);

            Log::info('Completion notification sent from admin', [
                'order_id' => $id,
                'order_number' => $order->order_number,
                'download_link' => $downloadLink,
                'success' => $result,
                'admin_id' => Auth::id()
            ]);

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Notifikasi penyelesaian berhasil dikirim'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim notifikasi penyelesaian'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending completion notification: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem'
            ]);
        }
    }

    /**
     * Send custom WhatsApp message
     */
    public function sendWhatsApp(Request $request, $id)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000'
            ]);

            $order = DocumentOrder::findOrFail($id);

            $result = $this->whatsappService->sendMessage(
                $order->customer_phone,
                $request->message
            );

            Log::info('Custom WhatsApp message sent from admin', [
                'order_id' => $id,
                'order_number' => $order->order_number,
                'message' => $request->message,
                'success' => $result,
                'admin_id' => Auth::id()
            ]);

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pesan WhatsApp berhasil dikirim'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim pesan WhatsApp'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending custom WhatsApp message: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}
