<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use Carbon\CarbonImmutable;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatWebhookEventRepositoryContract;
use JOOservices\LaravelChatGateway\Enums\WebhookEventStatus;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatWebhookEventRepository extends EloquentRepository implements ChatWebhookEventRepositoryContract
{
    public function __construct(ChatWebhookEvent $model)
    {
        parent::__construct($model);
    }

    public function createReceived(?ChatChannel $channel, string $provider, string $transport, ?string $externalEventId, ?string $eventType, array $payload, ?array $headers, ?string $payloadHash): ChatWebhookEvent
    {
        /** @var ChatWebhookEvent $event */
        $event = $this->newQuery()->create([
            'channel_id' => $channel?->getKey(),
            'provider' => $provider,
            'transport' => $transport,
            'external_event_id' => $externalEventId,
            'event_type' => $eventType,
            'status' => WebhookEventStatus::Received->value,
            'payload_hash' => $payloadHash,
            'headers' => $headers,
            'payload' => $payload,
        ]);

        return $event;
    }

    public function findDuplicate(string $provider, ?int $channelId, ?string $externalEventId, ?string $payloadHash): ?ChatWebhookEvent
    {
        $query = $this->newQuery()->where('provider', $provider);

        if ($channelId !== null) {
            $query->where('channel_id', $channelId);
        }

        if ($externalEventId !== null && $externalEventId !== '') {
            /** @var ?ChatWebhookEvent $event */
            $event = $query->where('external_event_id', $externalEventId)->first();

            return $event;
        }

        if ($payloadHash === null || $payloadHash === '') {
            return null;
        }

        /** @var ?ChatWebhookEvent $event */
        $event = $query->where('payload_hash', $payloadHash)->first();

        return $event;
    }

    public function markStatus(ChatWebhookEvent $event, string $status, ?string $reason = null): ChatWebhookEvent
    {
        $event->fill([
            'status' => $status,
            'reason' => $reason,
        ]);
        $event->save();

        return $event->refresh();
    }

    public function markProcessed(ChatWebhookEvent $event): ChatWebhookEvent
    {
        $event->fill([
            'status' => WebhookEventStatus::Processed->value,
            'processed_at' => CarbonImmutable::now(),
        ]);
        $event->save();

        return $event->refresh();
    }
}
