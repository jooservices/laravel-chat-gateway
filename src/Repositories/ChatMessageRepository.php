<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatMessageRepositoryContract;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatMessageRepository extends EloquentRepository implements ChatMessageRepositoryContract
{
    public function __construct(ChatMessage $model)
    {
        parent::__construct($model);
    }

    public function create(array $attributes): ChatMessage
    {
        /** @var ChatMessage $message */
        $message = $this->newQuery()->create($attributes);

        return $message;
    }

    public function findByProviderMessageId(string $provider, string $externalMessageId): ?ChatMessage
    {
        /** @var ?ChatMessage $message */
        $message = $this->newQuery()
            ->where('provider', $provider)
            ->where('external_message_id', $externalMessageId)
            ->first();

        return $message;
    }

    public function updateMessage(ChatMessage $message, array $attributes): ChatMessage
    {
        $message->fill($attributes);
        $message->save();

        return $message->refresh();
    }

    public function latestInbound(ChatConversation $conversation): ?ChatMessage
    {
        /** @var ?ChatMessage $message */
        $message = $conversation->messages()
            ->where('direction', 'inbound')
            ->latest('created_at')
            ->first();

        return $message;
    }
}
