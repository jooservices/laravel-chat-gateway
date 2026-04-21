<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatChannelRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatConversationRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ChannelServiceContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\Exceptions\ChannelNotFoundException;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class ChannelService implements ChannelServiceContract
{
    private CacheRepository $cache;

    public function __construct(
        private readonly ChatChannelRepositoryContract $channelRepository,
        private readonly ChatConversationRepositoryContract $conversationRepository,
        private readonly ChatGatewayManager $manager,
        CacheFactory $cacheFactory,
    ) {
        $this->cache = $cacheFactory->store((string) config('chat-gateway.cache.store', 'array'));
    }

    public function resolveInbound(string $provider, ?string $channelKey, array $payload, array $headers): ChatChannel
    {
        if (is_string($channelKey) && $channelKey !== '') {
            return $this->rememberChannel($provider, $channelKey, fn (): ?ChatChannel => $this->channelRepository->findByProviderAndKey($provider, $channelKey));
        }

        $inferredChannelKey = $this->manager->provider($provider)->parser()->inferChannelKey($payload, $headers);

        if (is_string($inferredChannelKey) && $inferredChannelKey !== '') {
            return $this->rememberChannel($provider, $inferredChannelKey, fn (): ?ChatChannel => $this->channelRepository->findByProviderAndKey($provider, $inferredChannelKey));
        }

        $channel = $this->channelRepository->findDefaultByProvider($provider);

        if ($channel === null) {
            throw new ChannelNotFoundException('Unable to resolve inbound channel.');
        }

        return $channel;
    }

    public function resolveProviderChannel(string $provider, ?string $channelKey = null): ChatChannel
    {
        if (is_string($channelKey) && $channelKey !== '') {
            return $this->rememberChannel($provider, $channelKey, fn (): ?ChatChannel => $this->channelRepository->findByProviderAndKey($provider, $channelKey));
        }

        $channel = $this->channelRepository->findDefaultByProvider($provider);

        if ($channel === null) {
            throw new ChannelNotFoundException('Unable to resolve channel for provider ['.$provider.'].');
        }

        return $channel;
    }

    public function resolveOutbound(OutboundMessageDto $message): ChatChannel
    {
        if ($message->conversationId !== null) {
            $conversation = $this->conversationRepository->findById($message->conversationId);

            if ($conversation === null) {
                throw new ChannelNotFoundException('Conversation channel could not be resolved.');
            }

            /** @var ChatChannel $channel */
            $channel = $conversation->channel;

            return $channel;
        }

        if ($message->channelId !== null) {
            $channel = $this->channelRepository->findById($message->channelId);

            if ($channel === null) {
                throw new ChannelNotFoundException('Channel could not be resolved by id.');
            }

            return $channel;
        }

        if ($message->channelKey !== null && $message->channelKey !== '') {
            $defaultProvider = (string) config('chat-gateway.default_provider', 'telegram');

            return $this->rememberChannel($defaultProvider, $message->channelKey, fn (): ?ChatChannel => $this->channelRepository->findByProviderAndKey($defaultProvider, $message->channelKey));
        }

        throw new ChannelNotFoundException('Unable to resolve outbound channel.');
    }

    /**
     * @param  callable(): ?ChatChannel  $resolver
     */
    private function rememberChannel(string $provider, string $channelKey, callable $resolver): ChatChannel
    {
        $cacheKey = sprintf('%s:channel:%s:%s', (string) config('chat-gateway.cache.prefix', 'chat_gateway'), $provider, $channelKey);

        /** @var ?ChatChannel $channel */
        $channel = $this->cache->remember($cacheKey, (int) config('chat-gateway.cache.channel_ttl_seconds', 300), static fn (): ?ChatChannel => $resolver());

        if ($channel === null) {
            throw new ChannelNotFoundException('Channel ['.$provider.':'.$channelKey.'] could not be resolved.');
        }

        return $channel;
    }
}
