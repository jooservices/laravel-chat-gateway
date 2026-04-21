<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\Contracts\Providers\ChatProviderContract;

interface ProviderRegistryServiceContract
{
    public function register(string $provider, ChatProviderContract $providerInstance): void;

    public function get(string $provider): ChatProviderContract;

    public function has(string $provider): bool;

    /**
     * @return array<string, ChatProviderContract>
     */
    public function all(): array;
}