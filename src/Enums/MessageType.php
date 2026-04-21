<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case File = 'file';
    case Button = 'button';
    case System = 'system';
}
