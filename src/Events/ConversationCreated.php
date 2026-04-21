<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatConversation;

final readonly class ConversationCreated
{
    public function __construct(public ChatConversation $conversation) {}
}
