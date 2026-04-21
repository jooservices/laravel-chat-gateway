<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatPollingState;

interface ChatPollingStateRepositoryContract
{
    public function findForChannel(string $provider, ?int $channelId): ?ChatPollingState;

    public function findOrCreateForChannel(string $provider, ?ChatChannel $channel = null): ChatPollingState;

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function updateOffset(ChatPollingState $state, int $offset, ?array $meta = null): ChatPollingState;

    public function resetOffset(ChatPollingState $state): ChatPollingState;
}