<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

interface ChatMessageRepositoryContract
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ChatMessage;

    public function findById(int $messageId): ?ChatMessage;

    public function findByProviderMessageId(string $provider, string $externalMessageId): ?ChatMessage;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateMessage(ChatMessage $message, array $attributes): ChatMessage;

    public function latestInbound(ChatConversation $conversation): ?ChatMessage;
}
