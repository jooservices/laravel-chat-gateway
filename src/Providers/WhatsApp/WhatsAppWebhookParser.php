<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\WhatsApp;

use Illuminate\Support\Arr;
use JOOservices\LaravelChatGateway\Contracts\Providers\InboundWebhookParserContract;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class WhatsAppWebhookParser implements InboundWebhookParserContract
{
    public function __construct(
        private readonly WhatsAppMapper $mapper,
    ) {}

    public function parse(array $payload, array $headers, ChatChannel $channel): InboundWebhookDto
    {
        unset($headers, $channel);

        return $this->mapper->mapInbound($payload);
    }

    public function inferChannelKey(array $payload, array $headers): ?string
    {
        return (string) (Arr::get($payload, 'entry.0.id') ?? $headers['x-channel-key'] ?? '') ?: null;
    }
}
