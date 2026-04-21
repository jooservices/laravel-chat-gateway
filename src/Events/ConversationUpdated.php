<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\Models\ChatConversation;

final readonly class ConversationUpdated
{
    public function __construct(public ChatConversation $conversation) {}
}
