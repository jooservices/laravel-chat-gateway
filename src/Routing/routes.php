<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JOOservices\LaravelChatGateway\Http\Controllers\WebhookController;

Route::post('/webhooks/{provider}/{channelKey?}', [WebhookController::class, 'store'])
    ->name('chat-gateway.webhooks.store');

Route::match(['get', 'post'], '/webhooks/{provider}/{channelKey?}/verify', [WebhookController::class, 'verify'])
    ->name('chat-gateway.webhooks.verify');
