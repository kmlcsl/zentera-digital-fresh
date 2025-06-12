<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\PaymentController;

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

// Fallback route for 404
Route::fallback(function () {
    return redirect()->route('home');
});


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
