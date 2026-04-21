<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Enums;

enum NormalizedEventType: string
{
    case Message = 'message';
    case DeliveryStatus = 'delivery_status';
    case ReadStatus = 'read_status';
    case CallbackAction = 'callback_action';
    case Membership = 'membership';
    case System = 'system';
}
