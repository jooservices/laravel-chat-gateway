<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
}
