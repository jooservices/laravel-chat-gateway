<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface ChannelServiceContract
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function resolveInbound(string $provider, ?string $channelKey, array $payload, array $headers): ChatChannel;

    public function resolveProviderChannel(string $provider, ?string $channelKey = null): ChatChannel;

    public function resolveOutbound(OutboundMessageDto $message): ChatChannel;
}
