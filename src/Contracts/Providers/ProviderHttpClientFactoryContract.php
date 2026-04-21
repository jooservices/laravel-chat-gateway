<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface ProviderHttpClientFactoryContract
{
    /**
     * @param  array<string, string>  $headers
     */
    public function make(ChatChannel $channel, array $headers = []): HttpClientInterface;
}
