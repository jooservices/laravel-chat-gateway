<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit;

use JOOservices\LaravelChatGateway\Services\DeduplicationService;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class DeduplicationServiceTest extends TestCase
{
    public function test_key_includes_channel_id(): void
    {
        $service = $this->app->make(DeduplicationService::class);

        $key = $service->makeKey('webhook_dedupe', 42, 'telegram', 'event-1', 'hash-1');

        $this->assertStringContainsString(':channel:42:', $key);
    }
}
