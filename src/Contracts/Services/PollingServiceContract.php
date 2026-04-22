<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\DTOs\PollingRunResultDto;

interface PollingServiceContract
{
    public function poll(string $provider, PollingBatchOptionsDto $options): PollingRunResultDto;
}
