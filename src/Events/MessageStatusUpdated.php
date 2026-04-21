<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatMessage;

final readonly class MessageStatusUpdated
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public ChatMessage $message,
        public ?string $oldStatus,
        public string $newStatus,
        public ?string $providerStatus = null,
        public ?array $payload = null,
    ) {}
}
