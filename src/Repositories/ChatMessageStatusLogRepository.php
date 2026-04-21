<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatMessageStatusLogRepositoryContract;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelChatGateway\Models\ChatMessageStatusLog;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatMessageStatusLogRepository extends EloquentRepository implements ChatMessageStatusLogRepositoryContract
{
    public function __construct(ChatMessageStatusLog $model)
    {
        parent::__construct($model);
    }

    public function createLog(ChatMessage $message, ?string $oldStatus, string $newStatus, ?string $providerStatus = null, ?array $payload = null): ChatMessageStatusLog
    {
        /** @var ChatMessageStatusLog $log */
        $log = $message->statusLogs()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'provider_status' => $providerStatus,
            'payload' => $payload,
        ]);

        return $log;
    }
}
