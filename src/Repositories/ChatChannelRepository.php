<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatChannelRepositoryContract;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatChannelRepository extends EloquentRepository implements ChatChannelRepositoryContract
{
    public function __construct(ChatChannel $model)
    {
        parent::__construct($model);
    }

    public function findByProviderAndKey(string $provider, string $channelKey): ?ChatChannel
    {
        /** @var ?ChatChannel $channel */
        $channel = $this->newQuery()
            ->where('provider', $provider)
            ->where('channel_key', $channelKey)
            ->first();

        return $channel;
    }

    public function findDefaultByProvider(string $provider): ?ChatChannel
    {
        /** @var ?ChatChannel $channel */
        $channel = $this->newQuery()
            ->where('provider', $provider)
            ->where('is_default', true)
            ->first();

        return $channel;
    }

    public function findById(int $channelId): ?ChatChannel
    {
        /** @var ?ChatChannel $channel */
        $channel = $this->newQuery()->find($channelId);

        return $channel;
    }
}
