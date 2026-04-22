<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

interface PollingCapableProviderContract extends ChatProviderContract
{
    public function pollingFetcher(): PollingUpdateFetcherContract;
}
