<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelChatGateway\Models\ChatMessageStatusLog;

interface ChatMessageStatusLogRepositoryContract
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function createLog(ChatMessage $message, ?string $oldStatus, string $newStatus, ?string $providerStatus = null, ?array $payload = null): ChatMessageStatusLog;
}
