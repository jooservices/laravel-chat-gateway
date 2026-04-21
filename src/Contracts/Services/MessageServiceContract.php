<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use Illuminate\Database\Eloquent\Collection;
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
     * @param  array<string, mixed>  $data
     */
    public function createOutboundFromApi(array $data): ChatMessage;

    public function queueSend(int $messageId): void;

    public function sendQueued(int $messageId): OutboundMessageResultDto;

    public function retry(int $messageId): void;

    public function getMessage(int $messageId): ?ChatMessage;

    /**
     * @return Collection<int, ChatMessage>
     */
    public function listConversationMessages(ChatConversation $conversation): Collection;

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function updateStatus(ChatMessage $message, string $newStatus, ?string $providerStatus = null, ?array $payload = null): ChatMessage;
}
