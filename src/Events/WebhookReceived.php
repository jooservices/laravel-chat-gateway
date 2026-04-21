<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;

final readonly class WebhookReceived
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public ChatWebhookEvent $webhookEvent,
        public array $payload,
    ) {}
}
