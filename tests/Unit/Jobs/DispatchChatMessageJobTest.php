<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit\Jobs;

use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Jobs\DispatchChatMessageJob;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;

final class DispatchChatMessageJobTest extends TestCase
{
    public function test_it_only_calls_message_service_send_queued_with_the_message_id(): void
    {
        $messageService = Mockery::mock(MessageServiceContract::class);
        $messageService->shouldReceive('sendQueued')->once()->with(55)->andReturn(new OutboundMessageResultDto(true, 'sent'));

        $job = new DispatchChatMessageJob(55);

        $job->handle($messageService);

        $this->assertTrue(true);
    }
}