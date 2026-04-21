<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\DTOs\ProviderChannelUpsertDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface ProviderChannelServiceContract
{
    public function registerChannel(string $provider, ProviderChannelUpsertDto $data): ChatChannel;

    public function updateChannel(ChatChannel $channel, ProviderChannelUpsertDto $data): ChatChannel;

    public function activate(ChatChannel $channel): ChatChannel;

    public function deactivate(ChatChannel $channel): ChatChannel;

    public function markDefault(ChatChannel $channel): ChatChannel;
}