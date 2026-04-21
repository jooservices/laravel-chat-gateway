<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum ChannelStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Disabled = 'disabled';
}
