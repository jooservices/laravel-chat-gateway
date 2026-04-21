<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Events;

use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

final readonly class OutgoingMessageSent
{
    public function __construct(
        public ChatMessage $message,
        public OutboundMessageResultDto $result,
    ) {}
}
