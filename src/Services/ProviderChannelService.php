<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use JOOservices\LaravelChatGateway\Contracts\Providers\SupportsCredentialSchemaContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatChannelRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderChannelServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderRegistryServiceContract;
use JOOservices\LaravelChatGateway\DTOs\ProviderChannelUpsertDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class ProviderChannelService implements ProviderChannelServiceContract
{
    public function __construct(
        private readonly ProviderRegistryServiceContract $registry,
        private readonly ChatChannelRepositoryContract $channelRepository,
    ) {}

    public function registerChannel(string $provider, ProviderChannelUpsertDto $data): ChatChannel
    {
        $this->validate($provider, $data);

        $channel = $this->channelRepository->createForProvider($provider, $data);

        if ($data->isDefault) {
            $channel = $this->channelRepository->markDefaultWithinProvider($channel);
        }

        return $channel;
    }

    public function updateChannel(ChatChannel $channel, ProviderChannelUpsertDto $data): ChatChannel
    {
        $this->validate($channel->provider, $data);

        $channel = $this->channelRepository->updateFromDto($channel, $data);

        if ($data->isDefault) {
            $channel = $this->channelRepository->markDefaultWithinProvider($channel);
        }

        return $channel;
    }

    public function activate(ChatChannel $channel): ChatChannel
    {
        return $this->channelRepository->setStatus($channel, 'active');
    }

    public function deactivate(ChatChannel $channel): ChatChannel
    {
        return $this->channelRepository->setStatus($channel, 'inactive');
    }

    public function markDefault(ChatChannel $channel): ChatChannel
    {
        return $this->channelRepository->markDefaultWithinProvider($channel);
    }

    private function validate(string $provider, ProviderChannelUpsertDto $data): void
    {
        $providerInstance = $this->registry->get($provider);

        if (! $providerInstance instanceof SupportsCredentialSchemaContract) {
            return;
        }

        $schema = $providerInstance->credentialSchema();
        $schema->validateCredentials($data->credentials);
        $schema->validateSettings($data->settings);
    }
}