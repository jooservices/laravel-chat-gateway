<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;

final readonly class WebhookVerified
{
    public function __construct(public ChatWebhookEvent $webhookEvent) {}
}
