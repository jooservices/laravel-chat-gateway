<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface InboundIngestionServiceContract
{
    /**
     * @param  array<string, mixed>|null  $rawPayload
     */
    public function ingest(string $provider, ChatChannel $channel, InboundWebhookDto $parsed, ?array $rawPayload = null): void;
}