<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use Illuminate\Database\Eloquent\Collection;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

interface ConversationServiceContract
{
    /**
     * @return Collection<int, ChatConversation>
     */
    public function listConversations(): Collection;

    public function getConversation(int $conversationId): ?ChatConversation;

    public function resolve(ChatChannel $channel, ChatContact $contact, InboundWebhookDto $event): ChatConversation;

    /**
     * @return Collection<int, ChatMessage>
     */
    public function listMessages(ChatConversation $conversation): Collection;

    public function close(ChatConversation $conversation): ChatConversation;
}
