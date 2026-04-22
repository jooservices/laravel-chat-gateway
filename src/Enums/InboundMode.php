<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum InboundMode: string
{
    case Poll = 'poll';
    case Callback = 'callback';
}
