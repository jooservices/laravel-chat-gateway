<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

interface ChatConversationRepositoryContract
{
    /**
     * @return Collection<int, ChatConversation>
     */
    public function listAll(): Collection;

    public function findByExternalChatId(int $channelId, string $externalChatId): ?ChatConversation;

    public function findById(int $conversationId): ?ChatConversation;

    /**
     * @return Collection<int, ChatMessage>
     */
    public function listMessages(ChatConversation $conversation): Collection;

    public function createOrUpdate(ChatChannel $channel, ChatContact $contact, ConversationContextDto $conversation): ChatConversation;
}
