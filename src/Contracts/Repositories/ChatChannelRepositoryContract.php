<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface ChatChannelRepositoryContract
{
    public function findByProviderAndKey(string $provider, string $channelKey): ?ChatChannel;

    public function findDefaultByProvider(string $provider): ?ChatChannel;

    public function findById(int $channelId): ?ChatChannel;
}
