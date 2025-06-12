<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// WhatsApp Webhook Routes - Otomatis tanpa CSRF
Route::post('webhook/whatsapp', [WhatsAppWebhookController::class, 'handleIncoming'])
    ->name('api.webhook.whatsapp.post');

Route::get('webhook/whatsapp', [WhatsAppWebhookController::class, 'handleIncoming'])
    ->name('api.webhook.whatsapp.get');

Route::get('webhook/whatsapp/test', [WhatsAppWebhookController::class, 'testWebhook'])
    ->name('api.webhook.whatsapp.test');

// Debug route
Route::get('webhook/status', function () {
    return response()->json([
        'status' => 'API webhook active',
        'timestamp' => now()->toDateTimeString(),
        'endpoints' => [
            'webhook_post' => url('/api/webhook/whatsapp'),
            'webhook_get' => url('/api/webhook/whatsapp'),
            'test' => url('/api/webhook/whatsapp/test')
        ]
    ]);
});
