<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;

interface ChatConversationRepositoryContract
{
    public function findByExternalChatId(int $channelId, string $externalChatId): ?ChatConversation;

    public function findById(int $conversationId): ?ChatConversation;

    public function createOrUpdate(ChatChannel $channel, ChatContact $contact, ConversationContextDto $conversation): ChatConversation;
}
