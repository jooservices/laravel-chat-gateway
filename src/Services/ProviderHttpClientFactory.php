<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class ProviderHttpClientFactory implements ProviderHttpClientFactoryContract
{
    public function make(ChatChannel $channel, array $headers = []): HttpClientInterface
    {
        /** @var array<string, mixed> $providerConfig */
        $providerConfig = config('chat-gateway.providers.'.$channel->provider, []);

        return ClientBuilder::create()
            ->withBaseUri((string) ($providerConfig['base_uri'] ?? ''))
            ->withTimeout((int) ($providerConfig['timeout'] ?? 15))
            ->withConnectTimeout((int) ($providerConfig['connect_timeout'] ?? 5))
            ->withVerifySsl((bool) ($providerConfig['verify_ssl'] ?? true))
            ->withHeaders($headers)
            ->build();
    }
}
