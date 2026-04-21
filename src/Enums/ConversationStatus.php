<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
