<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentUploadController;
use App\Classes\ListRoutes;

// Public Routes
$publicRoutes = (new ListRoutes)->getDataAuth();
foreach ($publicRoutes as $key_list => $value_list) {
    if (!empty($value_list['item'])) {
        $item = $value_list['item'];
        foreach ($item as $key => $value) {
            $value = (object)$value;
            if (!empty($value->method)) {
                $hasil = null;

                if (strtoupper($value->method) == 'GET') {
                    $hasil = Route::get($value->url, $value->controller);
                }
                if (strtoupper($value->method) == 'POST') {
                    $hasil = Route::post($value->url, $value->controller);
                }

                if (!empty($hasil) && !empty($value->name)) {
                    $hasil->name($value->name);
                }
            }
        }
    }
}

// Admin Routes
$adminRoutes = (new ListRoutes)->getDataAdmin();
foreach ($adminRoutes as $key_list => $value_list) {
    if (!empty($value_list['item'])) {
        $item = $value_list['item'];
        foreach ($item as $key => $value) {
            $value = (object)$value;
            if (!empty($value->method)) {
                $hasil = null;

                if (strtoupper($value->method) == 'GET') {
                    $hasil = Route::get($value->url, $value->controller);
                }
                if (strtoupper($value->method) == 'POST') {
                    $hasil = Route::post($value->url, $value->controller);
                }
                if (strtoupper($value->method) == 'PUT') {
                    $hasil = Route::put($value->url, $value->controller);
                }
                if (strtoupper($value->method) == 'DELETE') {
                    $hasil = Route::delete($value->url, $value->controller);
                }

                if (!empty($hasil) && !empty($value->name)) {
                    $hasil->name($value->name);
                }
            }
        }
    }
}

// Redirects
Route::get('/login', fn() => redirect('/admin/login'))->name('login');
Route::get('/admin', fn() => redirect('/admin/dashboard'));
Route::fallback(fn() => redirect('/'));

Route::get('/test-wa', function () {
    $controller = new \App\Http\Controllers\DocumentUploadController();

    $result = $controller->sendWhatsAppMessage(
        '6281330053572', // Nomor Zentera Digital
        'Tes integrasi FONNTE API berhasil!'
    );

    return response()->json([
        'status' => $result ? 'success' : 'error',
        'message' => $result ? 'Pesan terkirim' : 'Gagal mengirim'
    ]);
});
