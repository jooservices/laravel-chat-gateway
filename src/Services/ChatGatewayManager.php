<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use JOOservices\LaravelChatGateway\Contracts\Providers\ChatProviderContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderRegistryServiceContract;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class ChatGatewayManager
{
    public function __construct(
        private readonly ProviderRegistryServiceContract $providerRegistry,
    ) {}

    public function defaultProvider(): ChatProviderContract
    {
        return $this->provider((string) config('chat-gateway.default_provider', 'telegram'));
    }

    public function providerForChannel(ChatChannel $channel): ChatProviderContract
    {
        return $this->provider($channel->provider);
    }

    public function provider(string $provider): ChatProviderContract
    {
        return $this->providerRegistry->get($provider);
    }
}
