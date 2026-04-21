<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatMessage;

final readonly class OutgoingMessageFailed
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public ChatMessage $message,
        public string $reason,
        public ?array $payload = null,
    ) {}
}
