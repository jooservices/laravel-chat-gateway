<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\LaravelChatGatewayServiceProvider;
use JOOservices\LaravelChatGateway\Services\ProviderHttpClientFactory;
use JOOservices\LaravelController\Providers\LaravelControllerServiceProvider;
use JooServices\LaravelEvents\EventsServiceProvider;
use Jooservices\LaravelRepository\LaravelRepositoryServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        unset($app);

        return [
            LaravelChatGatewayServiceProvider::class,
            EventsServiceProvider::class,
            LaravelControllerServiceProvider::class,
            LaravelRepositoryServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        /** @var ConfigRepository $config */
        $config = $app['config'];

        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $config->set('cache.default', 'array');
        $config->set('queue.default', 'sync');
        $config->set('events.connection', 'mongodb');
        $config->set('events.eventsourcing.enabled', false);
        $config->set('events.event_log.enabled', false);
        $config->set('chat-gateway.cache.store', 'array');
        $config->set('chat-gateway.queue.outbound_enabled', false);
        $config->set('chat-gateway.events.audit_enabled', false);
        $config->set('chat-gateway.events.sourcing_enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function loadFixture(string $path): string
    {
        $fullPath = __DIR__.'/Fixtures/'.ltrim($path, '/');

        return file_get_contents($fullPath) ?: '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadJsonFixture(string $path): array
    {
        return json_decode($this->loadFixture($path), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function mockClientFactory(ProviderHttpClientFactory $factory): void
    {
        $this->app->instance(ProviderHttpClientFactory::class, $factory);
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
