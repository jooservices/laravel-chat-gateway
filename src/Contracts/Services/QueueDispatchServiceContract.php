<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

interface QueueDispatchServiceContract
{
    public function dispatchChatMessage(int $messageId): void;
}
