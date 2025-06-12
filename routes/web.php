<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
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

// WhatsApp Webhook Routes - CLEAN VERSION
Route::match(['get', 'post'], '/webhook/whatsapp', function (Illuminate\Http\Request $request) {
    try {
        // Handle GET request (verification)
        if ($request->isMethod('GET')) {
            return response()->json([
                'status' => 'webhook_active',
                'message' => 'WhatsApp webhook is ready',
                'timestamp' => now()->toDateTimeString(),
                'server' => 'Zentera Digital'
            ]);
        }

        // Handle POST request (actual webhook)
        $controller = new App\Http\Controllers\WhatsAppWebhookController();
        return $controller->handleIncoming($request);
    } catch (\Exception $e) {
        Log::error('Webhook route error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->name('webhook.whatsapp')
    ->withoutMiddleware([
        \App\Http\Middleware\VerifyCsrfToken::class,
        'throttle'
    ]);

// Test route
Route::get('/webhook/whatsapp/test', function (Illuminate\Http\Request $request) {
    try {
        $controller = new App\Http\Controllers\WhatsAppWebhookController();
        return $controller->testWebhook($request);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
})->name('webhook.whatsapp.test');

// Debug route
Route::get('/webhook-debug', function () {
    return response()->json([
        'routes' => [
            'webhook' => url('/webhook/whatsapp'),
            'test' => url('/webhook/whatsapp/test'),
        ],
        'csrf_disabled' => true,
        'timestamp' => now()
    ]);
});

Route::get('/debug-env', function () {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
        'fonnte_token_exists' => !empty(env('FONNTE_API_TOKEN')),
        'fonnte_token_length' => strlen(env('FONNTE_API_TOKEN', '')),
        'whatsapp_number' => env('WHATSAPP_NUMBER'),
        'services_fonnte_token' => !empty(config('services.fonnte.token')),
        'timestamp' => now()
    ]);
});
