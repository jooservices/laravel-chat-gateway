<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit\Services;

use Illuminate\Support\Facades\Queue;
use JOOservices\LaravelChatGateway\Contracts\Services\QueueDispatchServiceContract;
use JOOservices\LaravelChatGateway\Jobs\DispatchChatMessageJob;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class QueueDispatchServiceTest extends TestCase
{
    public function test_it_dispatches_chat_message_job_to_the_configured_outbound_queue_after_commit(): void
    {
        Queue::fake();

        config()->set('chat-gateway.queue.connection', 'redis');
        config()->set('chat-gateway.queue.queues.outbound', 'chat-outbound-priority');

        $this->app->make(QueueDispatchServiceContract::class)->dispatchChatMessage(77);

        Queue::assertPushed(DispatchChatMessageJob::class, function (DispatchChatMessageJob $job): bool {
            return $job->messageId === 77
                && $job->connection === 'redis'
                && $job->queue === 'chat-outbound-priority'
                && $job->afterCommit === true;
        });
    }
}