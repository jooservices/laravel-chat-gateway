<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use Illuminate\Database\Eloquent\Collection;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatChannelRepositoryContract;
use JOOservices\LaravelChatGateway\DTOs\ProviderChannelUpsertDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatChannelRepository extends EloquentRepository implements ChatChannelRepositoryContract
{
    public function __construct(ChatChannel $model)
    {
        parent::__construct($model);
    }

    public function listAll(): Collection
    {
        /** @var Collection<int, ChatChannel> $channels */
        $channels = $this->newQuery()
            ->orderByDesc('is_default')
            ->orderBy('provider')
            ->orderBy('channel_key')
            ->get();

        return $channels;
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

    public function createForProvider(string $provider, ProviderChannelUpsertDto $data): ChatChannel
    {
        /** @var ChatChannel $channel */
        $channel = $this->newQuery()->create([
            'provider' => $provider,
            'channel_key' => $data->channelKey,
            'name' => $data->name,
            'status' => $data->status,
            'is_default' => $data->isDefault,
            'credentials' => $data->credentials,
            'settings' => $data->settings,
            'webhook_secret' => $data->webhookSecret ?? '',
            'meta' => $data->meta,
        ]);

        return $channel;
    }

    public function updateFromDto(ChatChannel $channel, ProviderChannelUpsertDto $data): ChatChannel
    {
        $channel->fill([
            'channel_key' => $data->channelKey,
            'name' => $data->name,
            'status' => $data->status,
            'is_default' => $data->isDefault,
            'credentials' => $data->credentials,
            'settings' => $data->settings,
            'webhook_secret' => $data->webhookSecret ?? '',
            'meta' => $data->meta,
        ]);
        $channel->save();

        return $channel->refresh();
    }

    public function setStatus(ChatChannel $channel, string $status): ChatChannel
    {
        $channel->forceFill(['status' => $status])->save();

        return $channel->refresh();
    }

    public function markDefaultWithinProvider(ChatChannel $channel): ChatChannel
    {
        $this->newQuery()
            ->where('provider', $channel->provider)
            ->whereKeyNot($channel->getKey())
            ->update(['is_default' => false]);

        $channel->forceFill(['is_default' => true])->save();

        return $channel->refresh();
    }
}
