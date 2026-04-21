<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use JOOservices\LaravelChatGateway\Contracts\Services\QueueDispatchServiceContract;
use JOOservices\LaravelChatGateway\Jobs\DispatchChatMessageJob;

final class QueueDispatchService implements QueueDispatchServiceContract
{
    public function dispatchChatMessage(int $messageId): void
    {
        $dispatch = DispatchChatMessageJob::dispatch($messageId)
            ->onQueue((string) config('chat-gateway.queue.queues.outbound', 'chat-outbound'))
            ->afterCommit();

        $connection = config('chat-gateway.queue.connection');

        if (is_string($connection) && $connection !== '') {
            $dispatch->onConnection($connection);
        }
    }
}