<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface OutboundMessageSenderContract
{
    public function send(ChatChannel $channel, OutboundMessageDto $message): OutboundMessageResultDto;
}
