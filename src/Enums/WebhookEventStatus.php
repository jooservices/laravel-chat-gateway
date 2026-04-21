<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum WebhookEventStatus: string
{
    case Received = 'received';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Deduplicated = 'deduplicated';
    case Processed = 'processed';
    case Failed = 'failed';
}
