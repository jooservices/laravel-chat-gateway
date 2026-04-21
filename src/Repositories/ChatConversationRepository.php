<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use Carbon\CarbonImmutable;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatConversationRepositoryContract;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatConversationRepository extends EloquentRepository implements ChatConversationRepositoryContract
{
    public function __construct(ChatConversation $model)
    {
        parent::__construct($model);
    }

    public function findByExternalChatId(int $channelId, string $externalChatId): ?ChatConversation
    {
        /** @var ?ChatConversation $conversation */
        $conversation = $this->newQuery()
            ->where('channel_id', $channelId)
            ->where('external_chat_id', $externalChatId)
            ->first();

        return $conversation;
    }

    public function findById(int $conversationId): ?ChatConversation
    {
        /** @var ?ChatConversation $conversation */
        $conversation = $this->newQuery()->find($conversationId);

        return $conversation;
    }

    public function createOrUpdate(ChatChannel $channel, ChatContact $contact, ConversationContextDto $conversation): ChatConversation
    {
        $now = CarbonImmutable::now();

        /** @var ChatConversation $record */
        $record = $this->newQuery()->updateOrCreate(
            [
                'channel_id' => (int) $channel->getKey(),
                'external_chat_id' => $conversation->externalChatId,
            ],
            [
                'contact_id' => (int) $contact->getKey(),
                'status' => $conversation->status ?? config('chat-gateway.conversations.default_status', 'open'),
                'started_at' => $now,
                'last_message_at' => $now,
                'meta' => $conversation->meta,
            ]
        );

        return $record;
    }
}
