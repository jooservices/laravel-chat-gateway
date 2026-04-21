<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto send(array<string, mixed> $payload)
 */
final class ChatGateway extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'chat-gateway.manager';
    }
}
