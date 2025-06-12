<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('webhook/whatsapp', [WhatsAppWebhookController::class, 'handleIncoming']);
Route::get('webhook/whatsapp', [WhatsAppWebhookController::class, 'handleIncoming']);
Route::get('webhook/whatsapp/test', [WhatsAppWebhookController::class, 'testWebhook']);
