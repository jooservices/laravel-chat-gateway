<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Telegram;

use JOOservices\LaravelChatGateway\Contracts\Providers\InboundWebhookParserContract;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class TelegramWebhookParser implements InboundWebhookParserContract
{
    public function __construct(
        private readonly TelegramMapper $mapper,
    ) {}

    public function parse(array $payload, array $headers, ChatChannel $channel): InboundWebhookDto
    {
        unset($headers, $channel);

        return $this->mapper->mapInbound($payload);
    }

    public function inferChannelKey(array $payload, array $headers): ?string
    {
        unset($payload, $headers);

        return null;
    }
}
