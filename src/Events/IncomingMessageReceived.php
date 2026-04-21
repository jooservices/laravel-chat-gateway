<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

final readonly class IncomingMessageReceived
{
    public function __construct(
        public ChatMessage $message,
        public InboundWebhookDto $normalizedEvent,
    ) {}
}
