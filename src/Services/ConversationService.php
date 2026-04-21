<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatConversationRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ConversationServiceContract;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Events\ConversationClosed;
use JOOservices\LaravelChatGateway\Events\ConversationCreated;
use JOOservices\LaravelChatGateway\Events\ConversationUpdated;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;

final class ConversationService implements ConversationServiceContract
{
    public function __construct(
        private readonly ChatConversationRepositoryContract $conversationRepository,
        private readonly Dispatcher $events,
    ) {}

    public function listConversations(): Collection
    {
        return $this->conversationRepository->listAll();
    }

    public function getConversation(int $conversationId): ?ChatConversation
    {
        return $this->conversationRepository->findById($conversationId);
    }

    public function resolve(ChatChannel $channel, ChatContact $contact, InboundWebhookDto $event): ChatConversation
    {
        $existing = $this->conversationRepository->findByExternalChatId((int) $channel->getKey(), $event->conversation->externalChatId);
        $conversation = $this->conversationRepository->createOrUpdate($channel, $contact, $event->conversation);

        if ($existing === null) {
            $this->events->dispatch(new ConversationCreated($conversation));
        } else {
            $conversation->forceFill(['last_message_at' => CarbonImmutable::now()])->save();
            $this->events->dispatch(new ConversationUpdated($conversation));
        }

        return $conversation->refresh();
    }

    public function listMessages(ChatConversation $conversation): Collection
    {
        return $this->conversationRepository->listMessages($conversation);
    }

    public function close(ChatConversation $conversation): ChatConversation
    {
        $conversation->forceFill([
            'status' => 'closed',
            'closed_at' => CarbonImmutable::now(),
        ])->save();

        $conversation = $conversation->refresh();
        $this->events->dispatch(new ConversationClosed($conversation));

        return $conversation;
    }
}
