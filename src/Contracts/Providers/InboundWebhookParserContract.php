<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface InboundWebhookParserContract
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function parse(array $payload, array $headers, ChatChannel $channel): InboundWebhookDto;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function inferChannelKey(array $payload, array $headers): ?string;
}
