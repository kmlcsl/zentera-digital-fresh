<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products');

// Redirect default login ke admin login (karena hanya ada admin login)
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Document Upload Routes
Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('/upload/repair', [DocumentUploadController::class, 'repairForm'])->name('upload.repair');
    Route::get('/upload/format', [DocumentUploadController::class, 'formatForm'])->name('upload.format');
    Route::get('/upload/plagiarism', [DocumentUploadController::class, 'plagiarismForm'])->name('upload.plagiarism');

    Route::post('/upload/repair', [DocumentUploadController::class, 'repairSubmit'])->name('upload.repair.submit');
    Route::post('/upload/format', [DocumentUploadController::class, 'formatSubmit'])->name('upload.format.submit');
    Route::post('/upload/plagiarism', [DocumentUploadController::class, 'plagiarismSubmit'])->name('upload.plagiarism.submit');
});

// Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/{orderNumber}', [PaymentController::class, 'show'])->name('show');
    Route::post('/{orderNumber}/confirm', [PaymentController::class, 'confirm'])->name('confirm');
});

// Admin Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {

    // Ganti semua AdminLoginController dengan namespace baru:
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // Redirect /admin to dashboard or login
    Route::get('/', function () {
        if (Session::get('admin_logged_in') && Session::get('admin_id')) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('admin.login');
    });

    // Protected Admin Routes (Admin Only)
    Route::middleware('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
        Route::get('/dashboard/chart', [DashboardController::class, 'getChartData'])->name('dashboard.chart');
        Route::get('/', function () {
            if (Session::get('admin_logged_in') && Session::get('admin_id')) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('admin.login');
        });

        // Product Management
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\ProductController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\ProductController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('destroy');
            Route::post('/toggle-visibility', [\App\Http\Controllers\Admin\ProductController::class, 'toggleVisibility'])->name('toggle.visibility');
        });

        // Orders Management
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('filter', [OrderController::class, 'filter'])->name('filter');
            Route::get('create', [OrderController::class, 'create'])->name('create');
            Route::post('/', [OrderController::class, 'store'])->name('store');
            Route::get('{id}', [OrderController::class, 'show'])->name('show');
            Route::get('{id}/edit', [OrderController::class, 'edit'])->name('edit');
            Route::put('{id}', [OrderController::class, 'update'])->name('update');
            Route::delete('{id}', [OrderController::class, 'destroy'])->name('destroy');

            // AJAX Routes
            Route::get('{id}/details', [OrderController::class, 'getOrderDetails'])->name('details');
            Route::post('{id}/update-payment-status', [OrderController::class, 'updatePaymentStatus'])->name('update-payment-status');
            Route::get('{id}/download/{type}', [OrderController::class, 'downloadFile'])->name('download-file');
            Route::get('export', [OrderController::class, 'export'])->name('export');
        });

        // Settings Management
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('business', [SettingController::class, 'updateBusiness'])->name('business');
            Route::post('profile', [SettingController::class, 'updateProfile'])->name('profile');
            Route::put('/profile', [SettingController::class, 'updateProfile'])->name('profile.update');
            Route::post('password', [SettingController::class, 'updatePassword'])->name('password');
        });
    });
});

// Storage access route for Vercel (karena tidak ada symlink)
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);

    if (!file_exists($filePath)) {
        abort(404);
    }

    $mimeType = mime_content_type($filePath);

    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');

// Tambahkan route debug di web.php (HAPUS SETELAH TEST)
Route::get('/debug-files', function () {
    $publicPath = public_path();
    $paymentsPath = public_path('payments');

    $result = [
        'public_path' => $publicPath,
        'public_exists' => is_dir($publicPath),
        'payments_path' => $paymentsPath,
        'payments_exists' => is_dir($paymentsPath),
        'payment_files' => [],
        'vercel_structure' => [],
    ];

    // List payment files
    if (is_dir($paymentsPath)) {
        $result['payment_files'] = array_diff(scandir($paymentsPath), ['.', '..']);
    }

    // Check Vercel structure
    $basePath = base_path();
    $result['base_path'] = $basePath;
    $result['vercel_structure'] = [
        'api_exists' => is_dir($basePath . '/api'),
        'public_exists' => is_dir($basePath . '/public'),
        'public_payments_exists' => is_dir($basePath . '/public/payments'),
    ];

    // Test specific files
    $testFiles = ['dana.png', 'bca.png', 'mandiri.png', 'ovo.png'];
    foreach ($testFiles as $file) {
        $filePath = public_path("payments/{$file}");
        $result['test_files'][$file] = [
            'path' => $filePath,
            'exists' => file_exists($filePath),
            'size' => file_exists($filePath) ? filesize($filePath) : 0,
        ];
    }

    return response()->json($result, 200, [], JSON_PRETTY_PRINT);
});

// Fallback route for 404
Route::fallback(function () {
    return redirect()->route('home');
});
