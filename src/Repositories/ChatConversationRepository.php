<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatConversationRepositoryContract;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatConversationRepository extends EloquentRepository implements ChatConversationRepositoryContract
{
    public function __construct(ChatConversation $model)
    {
        parent::__construct($model);
    }

    public function listAll(): Collection
    {
        /** @var Collection<int, ChatConversation> $conversations */
        $conversations = $this->newQuery()
            ->with(['channel', 'contact'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return $conversations;
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
        $conversation = $this->newQuery()
            ->with(['channel', 'contact'])
            ->find($conversationId);

        return $conversation;
    }

    public function listMessages(ChatConversation $conversation): Collection
    {
        /** @var Collection<int, ChatMessage> $messages */
        $messages = $conversation->messages()
            ->with(['attachments', 'statusLogs'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $messages;
    }

    public function createOrUpdate(ChatChannel $channel, ChatContact $contact, ConversationContextDto $conversation): ChatConversation
    {
        $now = CarbonImmutable::now();
        $channelId = (int) $channel->getKey();

        /** @var ?ChatConversation $existing */
        $existing = $this->newQuery()
            ->where('channel_id', $channelId)
            ->where('external_chat_id', $conversation->externalChatId)
            ->first();

        if ($existing !== null) {
            $updates = [
                'contact_id' => (int) $contact->getKey(),
                'status' => $conversation->status ?? $existing->status,
                'last_message_at' => $now,
                'meta' => $conversation->meta,
            ];

            if ($conversation->chatType !== null) {
                $updates['chat_type'] = $conversation->chatType;
            }

            if ($conversation->chatTitle !== null) {
                $updates['chat_title'] = $conversation->chatTitle;
            }

            if ($conversation->chatUsername !== null) {
                $updates['chat_username'] = $conversation->chatUsername;
            }

            $existing->fill($updates);
            $existing->save();

            return $existing->refresh();
        }

        /** @var ChatConversation $record */
        $record = $this->newQuery()->create([
            'channel_id' => $channelId,
            'external_chat_id' => $conversation->externalChatId,
            'chat_type' => $conversation->chatType,
            'chat_title' => $conversation->chatTitle,
            'chat_username' => $conversation->chatUsername,
            'contact_id' => (int) $contact->getKey(),
            'status' => $conversation->status ?? config('chat-gateway.conversations.default_status', 'open'),
            'started_at' => $now,
            'last_message_at' => $now,
            'meta' => $conversation->meta,
        ]);

        return $record;
    }
}
