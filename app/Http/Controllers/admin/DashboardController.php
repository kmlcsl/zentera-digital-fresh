<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentOrder;
use App\Models\Product;

class DashboardController extends Controller
{
    /**
     * Admin dashboard
     */
    public function index()
    {
        if (!Session::get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $adminName = Session::get('admin_name', 'Admin');

        try {
            // REAL DATA dari database document_orders
            $totalOrders = DocumentOrder::count();
            $pendingOrders = DocumentOrder::where('payment_status', 'pending')->count();
            $paidOrders = DocumentOrder::where('payment_status', 'paid')->count();
            $completedOrders = DocumentOrder::where('payment_status', 'completed')->count();

            // Revenue bulan ini (hanya dari order yang completed)
            $monthlyRevenue = DocumentOrder::where('payment_status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('price');

            // Order minggu ini
            $weeklyOrders = DocumentOrder::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

            // Jumlah service aktif
            $activeServices = Product::where('is_active', true)->count();

            $stats = [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'paid_orders' => $paidOrders,
                'completed_orders' => $completedOrders,
                'monthly_revenue' => $monthlyRevenue,
                'weekly_orders' => $weeklyOrders,
                'active_services' => $activeServices
            ];

            // Recent orders REAL dari database (5 terakhir)
            $recent_orders = DocumentOrder::latest()
                ->limit(5)
                ->get()
                ->map(function ($order) {
                    $statusLabels = [
                        'pending' => 'Menunggu Pembayaran',
                        'paid' => 'Sedang Diproses',
                        'completed' => 'Selesai'
                    ];

                    $serviceLabels = [
                        'repair' => 'Perbaikan Dokumen',
                        'format' => 'Format Dokumen',
                        'plagiarism' => 'Cek Plagiarisme'
                    ];

                    return [
                        'id' => $order->order_number,
                        'customer' => $order->customer_name,
                        'service' => $serviceLabels[$order->service_type] ?? $order->service_name,
                        'price' => $order->price,
                        'status' => $statusLabels[$order->payment_status] ?? $order->payment_status,
                        'date' => $order->created_at->format('d M Y'),
                        'service_type' => $order->service_type,
                        'icon' => $order->service_icon,
                        'color' => $order->service_color,
                        'phone' => $order->customer_phone
                    ];
                });

            // Chart data REAL - 6 bulan terakhir
            $monthlyData = [];
            $monthlyRevenue = [];
            $monthlyOrders = [];

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyData[] = $date->format('M Y');

                // Revenue completed orders per bulan
                $revenue = DocumentOrder::where('payment_status', 'completed')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('price');
                $monthlyRevenue[] = (int)$revenue;

                // Total orders per bulan
                $orders = DocumentOrder::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count();
                $monthlyOrders[] = $orders;
            }

            $monthly_chart_data = [
                'labels' => $monthlyData,
                'revenue' => $monthlyRevenue,
                'orders' => $monthlyOrders
            ];

            // Service type statistics REAL dari database
            $serviceStats = DocumentOrder::selectRaw('service_type, COUNT(*) as count, SUM(price) as revenue, AVG(price) as avg_price')
                ->groupBy('service_type')
                ->get()
                ->map(function ($item) {
                    $serviceNames = [
                        'repair' => 'Perbaikan Dokumen',
                        'format' => 'Format Dokumen',
                        'plagiarism' => 'Cek Plagiarisme'
                    ];

                    $serviceIcons = [
                        'repair' => 'fas fa-wrench',
                        'format' => 'fas fa-list-ol',
                        'plagiarism' => 'fas fa-search'
                    ];

                    return [
                        'type' => $item->service_type,
                        'name' => $serviceNames[$item->service_type] ?? ucfirst($item->service_type),
                        'count' => $item->count,
                        'revenue' => $item->revenue,
                        'avg_price' => round($item->avg_price),
                        'icon' => $serviceIcons[$item->service_type] ?? 'fas fa-file'
                    ];
                });

            // Daily orders untuk chart mingguan
            $dailyOrders = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dailyOrders[] = [
                    'date' => $date->format('d M'),
                    'count' => DocumentOrder::whereDate('created_at', $date)->count(),
                    'revenue' => DocumentOrder::where('payment_status', 'completed')
                        ->whereDate('created_at', $date)
                        ->sum('price')
                ];
            }

            // Status distribution
            $statusDistribution = [
                'pending' => $pendingOrders,
                'paid' => $paidOrders,
                'completed' => $completedOrders
            ];

            // Jika tidak ada data, tampilkan pesan
            if ($totalOrders == 0) {
                session()->flash('info', 'Belum ada data order. Data akan muncul setelah ada order masuk.');
            }
        } catch (\Exception $e) {
            // Log error dan gunakan data kosong
            Log::error('Dashboard Error: ' . $e->getMessage());

            $stats = [
                'total_orders' => 0,
                'pending_orders' => 0,
                'paid_orders' => 0,
                'completed_orders' => 0,
                'monthly_revenue' => 0,
                'weekly_orders' => 0,
                'active_services' => Product::count() ?? 0
            ];

            $recent_orders = collect();
            $monthly_chart_data = [
                'labels' => [],
                'revenue' => [],
                'orders' => []
            ];
            $serviceStats = collect();
            $dailyOrders = [];
            $statusDistribution = ['pending' => 0, 'paid' => 0, 'completed' => 0];

            session()->flash('warning', 'Terjadi kesalahan saat memuat data dashboard.');
        }

        return view('admin.dashboard', compact(
            'stats',
            'recent_orders',
            'monthly_chart_data',
            'adminName',
            'serviceStats',
            'dailyOrders',
            'statusDistribution'
        ));
    }

    /**
     * Get dashboard stats for AJAX
     */
    public function getStats()
    {
        if (!Session::get('admin_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $stats = [
                'total_orders' => DocumentOrder::count(),
                'pending_orders' => DocumentOrder::where('payment_status', 'pending')->count(),
                'paid_orders' => DocumentOrder::where('payment_status', 'paid')->count(),
                'completed_orders' => DocumentOrder::where('payment_status', 'completed')->count(),
                'monthly_revenue' => DocumentOrder::where('payment_status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->sum('price'),
                'weekly_orders' => DocumentOrder::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'active_services' => Product::where('is_active', true)->count()
            ];

            return response()->json(['success' => true, 'stats' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load stats: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get chart data for dashboard
     */
    public function getChartData(Request $request)
    {
        if (!Session::get('admin_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $period = $request->get('period', 'monthly'); // monthly, weekly, daily

        try {
            $data = [];

            switch ($period) {
                case 'daily':
                    for ($i = 6; $i >= 0; $i--) {
                        $date = now()->subDays($i);
                        $data['labels'][] = $date->format('M d');
                        $data['revenue'][] = DocumentOrder::where('payment_status', 'completed')
                            ->whereDate('created_at', $date)
                            ->sum('price');
                        $data['orders'][] = DocumentOrder::whereDate('created_at', $date)->count();
                    }
                    break;

                case 'weekly':
                    for ($i = 5; $i >= 0; $i--) {
                        $startWeek = now()->subWeeks($i)->startOfWeek();
                        $endWeek = now()->subWeeks($i)->endOfWeek();
                        $data['labels'][] = 'Week ' . $startWeek->weekOfYear;
                        $data['revenue'][] = DocumentOrder::where('payment_status', 'completed')
                            ->whereBetween('created_at', [$startWeek, $endWeek])
                            ->sum('price');
                        $data['orders'][] = DocumentOrder::whereBetween('created_at', [$startWeek, $endWeek])->count();
                    }
                    break;

                default: // monthly
                    for ($i = 5; $i >= 0; $i--) {
                        $date = now()->subMonths($i);
                        $data['labels'][] = $date->format('M');
                        $data['revenue'][] = DocumentOrder::where('payment_status', 'completed')
                            ->whereMonth('created_at', $date->month)
                            ->whereYear('created_at', $date->year)
                            ->sum('price');
                        $data['orders'][] = DocumentOrder::whereMonth('created_at', $date->month)
                            ->whereYear('created_at', $date->year)
                            ->count();
                    }
                    break;
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load chart data: ' . $e->getMessage()], 500);
        }
    }
}
