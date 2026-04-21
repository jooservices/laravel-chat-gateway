<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Illuminate\Database\Eloquent\Collection;
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

    public function listChannels(): Collection
    {
        return $this->channelRepository->listAll();
    }

    public function getChannel(int $channelId): ?ChatChannel
    {
        return $this->channelRepository->findById($channelId);
    }

    public function registerChannel(string $provider, ProviderChannelUpsertDto $data): ChatChannel
    {
        $this->validate($provider, $data);

        $channel = $this->channelRepository->createForProvider($provider, $data);

        if ($data->isDefault) {
            $channel = $this->channelRepository->markDefaultWithinProvider($channel);
        }

        return $channel;
    }

    public function registerChannelFromApi(array $data): ChatChannel
    {
        $provider = (string) $data['provider'];

        return $this->registerChannel($provider, $this->mapUpsertDto($data));
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

    public function updateChannelFromApi(ChatChannel $channel, array $data): ChatChannel
    {
        $merged = [
            'channel_key' => $channel->channel_key,
            'name' => $data['name'] ?? $channel->name,
            'credentials' => $data['credentials'] ?? ($channel->credentials ?? []),
            'webhook_secret' => array_key_exists('webhook_secret', $data) ? $data['webhook_secret'] : $channel->webhook_secret,
            'settings' => $data['settings'] ?? ($channel->settings ?? []),
            'meta' => array_key_exists('meta', $data) ? $data['meta'] : $channel->meta,
            'is_default' => array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $channel->is_default,
            'status' => $data['status'] ?? $channel->status,
        ];

        return $this->updateChannel($channel, $this->mapUpsertDto($merged));
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function mapUpsertDto(array $data): ProviderChannelUpsertDto
    {
        return new ProviderChannelUpsertDto(
            channelKey: (string) $data['channel_key'],
            name: (string) $data['name'],
            credentials: is_array($data['credentials'] ?? null) ? $data['credentials'] : [],
            webhookSecret: isset($data['webhook_secret']) ? (string) $data['webhook_secret'] : null,
            settings: is_array($data['settings'] ?? null) ? $data['settings'] : [],
            meta: isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : null,
            isDefault: (bool) ($data['is_default'] ?? false),
            status: isset($data['status']) ? (string) $data['status'] : 'active',
        );
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