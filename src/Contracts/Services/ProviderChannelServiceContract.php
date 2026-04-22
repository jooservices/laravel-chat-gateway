<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use Illuminate\Database\Eloquent\Collection;
use JOOservices\LaravelChatGateway\DTOs\ProviderChannelUpsertDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface ProviderChannelServiceContract
{
    /**
     * @return Collection<int, ChatChannel>
     */
    public function listChannels(): Collection;

    public function getChannel(int $channelId): ?ChatChannel;

    public function registerChannel(string $provider, ProviderChannelUpsertDto $data): ChatChannel;

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerChannelFromApi(array $data): ChatChannel;

    public function updateChannel(ChatChannel $channel, ProviderChannelUpsertDto $data): ChatChannel;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateChannelFromApi(ChatChannel $channel, array $data): ChatChannel;

    public function activate(ChatChannel $channel): ChatChannel;

    public function deactivate(ChatChannel $channel): ChatChannel;

    public function markDefault(ChatChannel $channel): ChatChannel;
}
