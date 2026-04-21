<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit;

use JOOservices\LaravelChatGateway\Exceptions\UnsupportedProviderException;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramProvider;
use JOOservices\LaravelChatGateway\Services\ProviderRegistryService;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ProviderRegistryServiceTest extends TestCase
{
    public function test_it_registers_and_resolves_providers(): void
    {
        $registry = new ProviderRegistryService();
        $provider = $this->app->make(TelegramProvider::class);

        $registry->register($provider->name(), $provider);

        $this->assertTrue($registry->has('telegram'));
        $this->assertSame($provider, $registry->get('telegram'));
        $this->assertCount(1, $registry->all());
    }

    public function test_it_throws_for_unsupported_provider(): void
    {
        $registry = new ProviderRegistryService();

        $this->expectException(UnsupportedProviderException::class);
        $this->expectExceptionMessage('Unsupported provider [signal].');

        $registry->get('signal');
    }
}