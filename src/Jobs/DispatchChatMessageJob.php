<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;

final class DispatchChatMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $messageId,
    ) {
        $this->tries = (int) config('chat-gateway.queue.outbound.tries', 3);
        $this->timeout = (int) config('chat-gateway.queue.outbound.timeout', 120);

        $configuredBackoff = config('chat-gateway.queue.outbound.backoff', [10, 30, 60]);

        if (is_array($configuredBackoff)) {
            $this->backoff = array_values(array_map(static fn (mixed $delay): int => (int) $delay, $configuredBackoff));
        }
    }

    public function handle(MessageServiceContract $messageService): void
    {
        $messageService->sendQueued($this->messageId);
    }
}
