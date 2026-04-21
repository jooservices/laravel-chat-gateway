<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;

interface ChatWebhookEventRepositoryContract
{
    /**
     * @param  array<string, mixed>|null  $headers
     * @param  array<string, mixed>  $payload
     */
    public function createReceived(?ChatChannel $channel, string $provider, string $transport, ?string $externalEventId, ?string $eventType, array $payload, ?array $headers, ?string $payloadHash): ChatWebhookEvent;

    public function findDuplicate(string $provider, ?string $externalEventId, ?string $payloadHash): ?ChatWebhookEvent;

    public function markStatus(ChatWebhookEvent $event, string $status, ?string $reason = null): ChatWebhookEvent;

    public function markProcessed(ChatWebhookEvent $event): ChatWebhookEvent;
}
