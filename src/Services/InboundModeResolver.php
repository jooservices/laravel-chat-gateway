<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Illuminate\Support\Arr;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundModeResolverContract;
use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\Enums\InboundMode;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class InboundModeResolver implements InboundModeResolverContract
{
    public function resolve(ChatChannel $channel): InboundMode
    {
        $configured = Arr::get($channel->settings ?? [], 'inbound_mode')
            ?? config('chat-gateway.providers.'.$channel->provider.'.inbound_mode', config('chat-gateway.inbound.default_mode', InboundMode::Poll->value));

        return InboundMode::from((string) $configured);
    }

    public function isPollingEnabled(ChatChannel $channel): bool
    {
        return (bool) (Arr::get($channel->settings ?? [], 'polling.enabled')
            ?? config('chat-gateway.providers.'.$channel->provider.'.polling.enabled', false));
    }

    public function isWebhookEnabled(ChatChannel $channel): bool
    {
        return (bool) (Arr::get($channel->settings ?? [], 'webhook.enabled')
            ?? config('chat-gateway.providers.'.$channel->provider.'.webhook.enabled', true));
    }

    public function pollingOptions(ChatChannel $channel, PollingBatchOptionsDto $options): PollingBatchOptionsDto
    {
        /** @var list<string>|null $configuredAllowedUpdates */
        $configuredAllowedUpdates = Arr::get($channel->settings ?? [], 'polling.allowed_updates')
            ?? config('chat-gateway.providers.'.$channel->provider.'.polling.allowed_updates');

        return new PollingBatchOptionsDto(
            channelKey: $options->channelKey,
            timeout: $options->timeout ?? (int) config('chat-gateway.providers.'.$channel->provider.'.polling.timeout', 30),
            limit: $options->limit ?? (int) config('chat-gateway.providers.'.$channel->provider.'.polling.limit', 100),
            allowedUpdates: $options->allowedUpdates ?? $configuredAllowedUpdates,
            resetOffset: $options->resetOffset,
        );
    }
}
