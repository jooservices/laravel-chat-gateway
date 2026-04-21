<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatMessage;

final readonly class OutgoingMessageQueued
{
    public function __construct(public ChatMessage $message) {}
}
