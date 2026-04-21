<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;

final class DeduplicationService
{
    public function __construct(
        private readonly CacheFactory $cacheFactory,
    ) {}

    public function makeKey(string $namespace, int $channelId, string $provider, ?string $externalEventId, string $payloadHash): string
    {
        $identifier = $externalEventId !== null && $externalEventId !== '' ? $externalEventId : $payloadHash;

        return sprintf(
            '%s:%s:channel:%d:%s:%s',
            (string) config('chat-gateway.cache.prefix', 'chat_gateway'),
            $namespace,
            $channelId,
            $provider,
            $identifier,
        );
    }

    public function has(string $namespace, int $channelId, string $provider, ?string $externalEventId, string $payloadHash): bool
    {
        return $this->cacheFactory
            ->store((string) config('chat-gateway.cache.store', 'array'))
            ->has($this->makeKey($namespace, $channelId, $provider, $externalEventId, $payloadHash));
    }

    public function put(string $namespace, int $channelId, string $provider, ?string $externalEventId, string $payloadHash, int $ttlSeconds): void
    {
        $this->cacheFactory
            ->store((string) config('chat-gateway.cache.store', 'array'))
            ->put($this->makeKey($namespace, $channelId, $provider, $externalEventId, $payloadHash), true, $ttlSeconds);
    }
}