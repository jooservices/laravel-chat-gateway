<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum ChatProvider: string
{
    case Telegram = 'telegram';
    case Viber = 'viber';
    case WhatsApp = 'whatsapp';
}
