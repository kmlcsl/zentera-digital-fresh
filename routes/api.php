<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes loaded by RouteServiceProvider within "api" middleware group
| Prefix: /api
*/

// API INFO
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Zentera Digital API',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString(),
        'endpoints' => [
            'GET /api/' => 'API Information',
            'GET /api/test' => 'Test endpoint',
            'POST /api/webhook/whatsapp' => 'WhatsApp Webhook',
            'GET /api/webhook/whatsapp' => 'WhatsApp Webhook Verification'
        ]
    ]);
});

// TEST ENDPOINT
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API test successful',
        'timestamp' => now()->toDateTimeString(),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ]
    ]);
});

// WHATSAPP WEBHOOK ROUTES
Route::match(['get', 'post'], '/webhook/whatsapp', function (Request $request) {
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
})->name('api.webhook.whatsapp');

// WEBHOOK TEST
Route::get('/webhook/whatsapp/test', function (Request $request) {
    try {
        $controller = new App\Http\Controllers\WhatsAppWebhookController();
        return $controller->testWebhook($request);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->name('api.webhook.whatsapp.test');

// WHATSAPP TEST ENDPOINTS
Route::get('/test-whatsapp', function () {
    try {
        $whatsappService = app(App\Services\WhatsAppService::class);
        $result = $whatsappService->sendMessage('6281383894808', 'Test message dari sistem');

        return response()->json([
            'status' => 'success',
            'result' => $result,
            'service_class' => get_class($whatsappService)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

// DATABASE TEST
Route::get('/test-order', function () {
    $order = App\Models\DocumentOrder::where('order_number', 'DOC20250612004')->first();

    if ($order) {
        return response()->json([
            'status' => 'found',
            'order' => $order->only(['id', 'order_number', 'status', 'created_at']),
            'total_orders' => App\Models\DocumentOrder::count()
        ]);
    } else {
        return response()->json([
            'status' => 'not_found',
            'total_orders' => App\Models\DocumentOrder::count(),
            'recent_orders' => App\Models\DocumentOrder::latest()->take(5)->pluck('order_number')
        ]);
    }
});

// DEBUG ENDPOINTS
Route::get('/debug-env', function () {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
        'fonnte_token_exists' => !empty(env('FONNTE_API_TOKEN')),
        'whatsapp_number' => env('WHATSAPP_NUMBER'),
        'timestamp' => now()
    ]);
});

Route::get('/debug-routes', function () {
    return response()->json([
        'routes' => [
            'webhook' => url('/api/webhook/whatsapp'),
            'test' => url('/api/webhook/whatsapp/test'),
            'home' => url('/'),
        ],
        'current_url' => request()->url(),
        'timestamp' => now()
    ]);
});
