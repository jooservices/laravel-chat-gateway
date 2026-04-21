<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use JOOservices\LaravelChatGateway\Contracts\Providers\PollingCapableProviderContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatPollingStateRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatWebhookEventRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ChannelServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundIngestionServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundModeResolverContract;
use JOOservices\LaravelChatGateway\Contracts\Services\PollingServiceContract;
use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\DTOs\PollingRunResultDto;
use JOOservices\LaravelChatGateway\Enums\InboundMode;
use JOOservices\LaravelChatGateway\Enums\WebhookEventStatus;
use JOOservices\LaravelChatGateway\Exceptions\ChatGatewayException;

final class PollingService implements PollingServiceContract
{
    public function __construct(
        private readonly ChannelServiceContract $channelService,
        private readonly ChatPollingStateRepositoryContract $pollingStateRepository,
        private readonly ChatWebhookEventRepositoryContract $webhookEventRepository,
        private readonly InboundModeResolverContract $inboundModeResolver,
        private readonly InboundIngestionServiceContract $inboundIngestionService,
        private readonly ChatGatewayManager $manager,
        private readonly CacheFactory $cacheFactory,
    ) {}

    public function poll(string $provider, PollingBatchOptionsDto $options): PollingRunResultDto
    {
        $channel = $this->channelService->resolveProviderChannel($provider, $options->channelKey);
        $mode = $this->inboundModeResolver->resolve($channel);

        if ($mode !== InboundMode::Poll) {
            throw new ChatGatewayException('Polling is disabled because inbound mode is configured as callback for channel ['.$channel->channel_key.'].');
        }

        if (! $this->inboundModeResolver->isPollingEnabled($channel)) {
            throw new ChatGatewayException('Polling is not enabled for channel ['.$channel->channel_key.'].');
        }

        $providerInstance = $this->manager->providerForChannel($channel);

        if (! $providerInstance instanceof PollingCapableProviderContract) {
            throw new ChatGatewayException('Provider ['.$provider.'] does not support polling.');
        }

        $state = $this->pollingStateRepository->findOrCreateForChannel($provider, $channel);

        if ($options->resetOffset) {
            $state = $this->pollingStateRepository->resetOffset($state);
        }

        $resolvedOptions = $this->inboundModeResolver->pollingOptions($channel, $options);
        $result = $providerInstance->pollingFetcher()->fetch($channel, $state, $resolvedOptions);

        $processedCount = 0;
        $deduplicatedCount = 0;
        $failedCount = 0;

        foreach ($result->updates as $update) {
            $parsed = $providerInstance->parser()->parse($update, [], $channel);
            $payloadHash = hash('sha256', json_encode($update, JSON_THROW_ON_ERROR));
            $duplicate = $this->webhookEventRepository->findDuplicate($provider, $parsed->externalEventId, $payloadHash);
            $duplicateByCache = $this->pollDedupeKeyAlreadySeen($provider, $parsed->externalEventId, $payloadHash);
            $event = $duplicate;

            if ($event === null) {
                $event = $this->webhookEventRepository->createReceived(
                    $channel,
                    $provider,
                    'poll',
                    $parsed->externalEventId,
                    $parsed->eventType,
                    $update,
                    null,
                    $payloadHash,
                );
            }

            if ($duplicate !== null || $duplicateByCache) {
                $this->webhookEventRepository->markStatus($event, WebhookEventStatus::Deduplicated->value, 'Duplicate polled event.');
                $deduplicatedCount++;
                $state = $this->pollingStateRepository->updateOffset($state, $this->nextOffsetFor($update, $state->offset), [
                    'last_update_id' => $parsed->externalEventId,
                    'channel_key' => $channel->channel_key,
                ]);

                continue;
            }

            try {
                $this->storePollDedupeKey($provider, $parsed->externalEventId, $payloadHash);
                $this->webhookEventRepository->markStatus($event, WebhookEventStatus::Verified->value);
                $this->inboundIngestionService->ingest($provider, $channel, $parsed, $update);
                $this->webhookEventRepository->markProcessed($event);
                $processedCount++;
                $state = $this->pollingStateRepository->updateOffset($state, $this->nextOffsetFor($update, $state->offset), [
                    'last_update_id' => $parsed->externalEventId,
                    'channel_key' => $channel->channel_key,
                ]);
            } catch (\Throwable $exception) {
                $failedCount++;
                $this->webhookEventRepository->markStatus($event, WebhookEventStatus::Rejected->value, $exception->getMessage());

                throw $exception;
            }
        }

        return new PollingRunResultDto(
            provider: $provider,
            channelKey: $channel->channel_key,
            processedCount: $processedCount,
            deduplicatedCount: $deduplicatedCount,
            fetchedCount: count($result->updates),
            failedCount: $failedCount,
            offset: $state->offset,
        );
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function nextOffsetFor(array $update, int $currentOffset): int
    {
        $updateId = (int) ($update['update_id'] ?? 0);

        return $updateId > 0 ? max($currentOffset, $updateId + 1) : $currentOffset;
    }

    private function pollDedupeKeyAlreadySeen(string $provider, ?string $externalEventId, string $payloadHash): bool
    {
        $cacheStore = $this->cacheFactory->store((string) config('chat-gateway.cache.store', 'array'));
        $identifier = $externalEventId !== null && $externalEventId !== '' ? $externalEventId : $payloadHash;
        $cacheKey = sprintf('%s:poll_dedupe:%s:%s', (string) config('chat-gateway.cache.prefix', 'chat_gateway'), $provider, $identifier);

        return $cacheStore->has($cacheKey);
    }

    private function storePollDedupeKey(string $provider, ?string $externalEventId, string $payloadHash): void
    {
        $cacheStore = $this->cacheFactory->store((string) config('chat-gateway.cache.store', 'array'));
        $identifier = $externalEventId !== null && $externalEventId !== '' ? $externalEventId : $payloadHash;
        $cacheKey = sprintf('%s:poll_dedupe:%s:%s', (string) config('chat-gateway.cache.prefix', 'chat_gateway'), $provider, $identifier);

        $cacheStore->put($cacheKey, true, (int) config('chat-gateway.cache.poll_dedupe_ttl_seconds', 300));
    }
}