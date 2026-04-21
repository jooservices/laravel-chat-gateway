<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use JOOservices\LaravelChatGateway\DTOs\ProviderChannelUpsertDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface ChatChannelRepositoryContract
{
    /**
     * @return Collection<int, ChatChannel>
     */
    public function listAll(): Collection;

    public function findByProviderAndKey(string $provider, string $channelKey): ?ChatChannel;

    public function findDefaultByProvider(string $provider): ?ChatChannel;

    public function findById(int $channelId): ?ChatChannel;

    public function createForProvider(string $provider, ProviderChannelUpsertDto $data): ChatChannel;

    public function updateFromDto(ChatChannel $channel, ProviderChannelUpsertDto $data): ChatChannel;

    public function setStatus(ChatChannel $channel, string $status): ChatChannel;

    public function markDefaultWithinProvider(ChatChannel $channel): ChatChannel;
}
