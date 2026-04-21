<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use JOOservices\LaravelChatGateway\Contracts\Providers\ChatProviderContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderRegistryServiceContract;
use JOOservices\LaravelChatGateway\Exceptions\UnsupportedProviderException;

final class ProviderRegistryService implements ProviderRegistryServiceContract
{
    /**
     * @var array<string, ChatProviderContract>
     */
    private array $providers = [];

    public function register(string $provider, ChatProviderContract $providerInstance): void
    {
        $this->providers[$provider] = $providerInstance;
    }

    public function get(string $provider): ChatProviderContract
    {
        $providerInstance = $this->providers[$provider] ?? null;

        if ($providerInstance === null) {
            throw new UnsupportedProviderException('Unsupported provider ['.$provider.'].');
        }

        return $providerInstance;
    }

    public function has(string $provider): bool
    {
        return isset($this->providers[$provider]);
    }

    public function all(): array
    {
        return $this->providers;
    }
}