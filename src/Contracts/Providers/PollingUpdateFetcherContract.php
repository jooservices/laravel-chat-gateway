<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\DTOs\PollingFetchResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatPollingState;

interface PollingUpdateFetcherContract
{
    public function fetch(ChatChannel $channel, ChatPollingState $state, PollingBatchOptionsDto $options): PollingFetchResultDto;
}
