<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatPollingStateRepositoryContract;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatPollingState;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatPollingStateRepository extends EloquentRepository implements ChatPollingStateRepositoryContract
{
    public function __construct(ChatPollingState $model)
    {
        parent::__construct($model);
    }

    public function findForChannel(string $provider, ?int $channelId): ?ChatPollingState
    {
        /** @var ?ChatPollingState $state */
        $state = $this->newQuery()
            ->where('provider', $provider)
            ->where('channel_id', $channelId)
            ->first();

        return $state;
    }

    public function findOrCreateForChannel(string $provider, ?ChatChannel $channel = null): ChatPollingState
    {
        /** @var ChatPollingState $state */
        $state = $this->newQuery()->firstOrCreate(
            [
                'provider' => $provider,
                'channel_id' => $channel?->getKey(),
            ],
            [
                'offset' => 0,
            ],
        );

        return $state;
    }

    public function updateOffset(ChatPollingState $state, int $offset, ?array $meta = null): ChatPollingState
    {
        $state->fill([
            'offset' => $offset,
            'meta' => $meta ?? $state->meta,
        ]);
        $state->save();

        return $state->refresh();
    }

    public function resetOffset(ChatPollingState $state): ChatPollingState
    {
        $state->fill([
            'offset' => 0,
        ]);
        $state->save();

        return $state->refresh();
    }
}
