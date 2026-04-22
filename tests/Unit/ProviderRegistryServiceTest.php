<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit;

use JOOservices\LaravelChatGateway\Contracts\Services\ProviderRegistryServiceContract;
use JOOservices\LaravelChatGateway\Exceptions\UnsupportedProviderException;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramProvider;
use JOOservices\LaravelChatGateway\Services\ChatGatewayManager;
use JOOservices\LaravelChatGateway\Services\ProviderRegistryService;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ProviderRegistryServiceTest extends TestCase
{
    public function test_it_registers_and_resolves_providers(): void
    {
        $registry = new ProviderRegistryService;
        $provider = $this->app->make(TelegramProvider::class);

        $registry->register($provider->name(), $provider);

        $this->assertTrue($registry->has('telegram'));
        $this->assertSame($provider, $registry->get('telegram'));
        $this->assertCount(1, $registry->all());
    }

    public function test_it_throws_for_unsupported_provider(): void
    {
        $registry = new ProviderRegistryService;

        $this->expectException(UnsupportedProviderException::class);
        $this->expectExceptionMessage('Unsupported provider [signal].');

        $registry->get('signal');
    }

    public function test_container_registry_is_singleton_and_registered_for_manager_resolution(): void
    {
        $first = $this->app->make(ProviderRegistryServiceContract::class);
        $second = $this->app->make(ProviderRegistryServiceContract::class);
        $manager = $this->app->make(ChatGatewayManager::class);

        $this->assertSame($first, $second);
        $this->assertTrue($first->has('telegram'));
        $this->assertTrue($first->has('viber'));
        $this->assertTrue($first->has('whatsapp'));
        $this->assertSame($first->get('telegram'), $manager->provider('telegram'));
    }
}
