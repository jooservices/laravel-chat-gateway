<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Console;

use JOOservices\LaravelChatGateway\Contracts\Services\PollingServiceContract;
use JOOservices\LaravelChatGateway\DTOs\PollingRunResultDto;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;

final class GatewayPollCommandTest extends TestCase
{
    public function test_it_runs_the_gateway_poll_command_for_telegram_once(): void
    {
        $service = Mockery::mock(PollingServiceContract::class);
        $service->shouldReceive('poll')->once()->andReturn(new PollingRunResultDto(
            provider: 'telegram',
            channelKey: 'telegram-default',
            processedCount: 1,
            deduplicatedCount: 0,
            fetchedCount: 1,
            failedCount: 0,
            offset: 1002,
        ));
        $this->app->instance(PollingServiceContract::class, $service);

        $this->artisan('gateway:poll', ['provider' => 'telegram', '--once' => true])
            ->assertExitCode(0);
    }

    public function test_it_exits_cleanly_when_an_idle_poll_returns_no_updates(): void
    {
        $service = Mockery::mock(PollingServiceContract::class);
        $service->shouldReceive('poll')->once()->andReturn(new PollingRunResultDto(
            provider: 'telegram',
            channelKey: 'telegram-default',
            processedCount: 0,
            deduplicatedCount: 0,
            fetchedCount: 0,
            failedCount: 0,
            offset: 0,
        ));
        $this->app->instance(PollingServiceContract::class, $service);

        $this->artisan('gateway:poll', ['provider' => 'telegram', '--once' => true])
            ->assertExitCode(0);
    }
}
