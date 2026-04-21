<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatMessage;

final readonly class OutgoingMessageCreated
{
    public function __construct(public ChatMessage $message) {}
}
