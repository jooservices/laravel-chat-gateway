<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

interface MessageServiceContract
{
    /**
     * @param  array<string, mixed>|null  $rawPayload
     */
    public function createInbound(ChatConversation $conversation, InboundWebhookDto $event, ?array $rawPayload = null): ?ChatMessage;

    public function send(OutboundMessageDto $message): OutboundMessageResultDto;

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function updateStatus(ChatMessage $message, string $newStatus, ?string $providerStatus = null, ?array $payload = null): ChatMessage;
}
