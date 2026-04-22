<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\Enums\InboundMode;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface InboundModeResolverContract
{
    public function resolve(ChatChannel $channel): InboundMode;

    public function isPollingEnabled(ChatChannel $channel): bool;

    public function isWebhookEnabled(ChatChannel $channel): bool;

    public function pollingOptions(ChatChannel $channel, PollingBatchOptionsDto $options): PollingBatchOptionsDto;
}
