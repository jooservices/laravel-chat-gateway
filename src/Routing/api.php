<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JOOservices\LaravelChatGateway\Http\Controllers\Api\V1\ChannelController;
use JOOservices\LaravelChatGateway\Http\Controllers\Api\V1\ConversationController;
use JOOservices\LaravelChatGateway\Http\Controllers\Api\V1\MessageController;
use JOOservices\LaravelChatGateway\Http\Controllers\Api\V1\WebhookController;

Route::prefix('api/v1/chat-gateway')
    ->name('api.v1.chat-gateway.')
    ->group(function (): void {
        Route::prefix('webhooks')
            ->name('webhooks.')
            ->group(function (): void {
                Route::post('telegram', [WebhookController::class, 'telegram'])->name('telegram');
                Route::post('whatsapp', [WebhookController::class, 'whatsapp'])->name('whatsapp');
                Route::post('viber', [WebhookController::class, 'viber'])->name('viber');
            });

        Route::middleware((array) config('chat-gateway.api.middleware.protected', []))
            ->group(function (): void {
                Route::get('channels', [ChannelController::class, 'index'])->name('channels.index');
                Route::post('channels', [ChannelController::class, 'store'])->name('channels.store');
                Route::get('channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
                Route::patch('channels/{channel}', [ChannelController::class, 'update'])->name('channels.update');

                Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
                Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
                Route::post('messages/{message}/retry', [MessageController::class, 'retry'])->name('messages.retry');

                Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
                Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
                Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('conversations.messages');
            });
    });
