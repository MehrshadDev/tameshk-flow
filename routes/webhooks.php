<?php

use App\Http\Controllers\BaleWebhookController;
use App\Http\Controllers\ChatWebhookController;
use App\Http\Controllers\FormWebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WebhookTriggerController;
use App\Http\Controllers\WooCommerceWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhook/{token}/chat', [ChatWebhookController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('webhooks.chat.show');

Route::post('/webhook/{token}/chat', [ChatWebhookController::class, 'message'])
    ->middleware('throttle:20,1')
    ->name('webhooks.chat.message');

Route::get('/webhook/{token}/form', [FormWebhookController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('webhooks.form.show');

Route::post('/webhook/{token}/form', [FormWebhookController::class, 'submit'])
    ->middleware('throttle:30,1')
    ->name('webhooks.form.submit');

Route::post('/webhook/{token}/telegram', TelegramWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('webhooks.telegram');

Route::post('/webhook/{token}/bale/{secret}', BaleWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('webhooks.bale');

Route::post('/webhook/{token}/woocommerce', WooCommerceWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('webhooks.woocommerce');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/webhook-test/{path}', [WebhookTriggerController::class, 'test'])
    ->where('path', '.+')
    ->middleware('throttle:120,1')
    ->name('webhooks.custom.test');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/webhook/{path}', [WebhookTriggerController::class, 'production'])
    ->where('path', '.+')
    ->middleware('throttle:300,1')
    ->name('webhooks.custom.production');
