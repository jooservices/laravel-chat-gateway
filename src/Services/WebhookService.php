<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatWebhookEventRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ChannelServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundIngestionServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundModeResolverContract;
use JOOservices\LaravelChatGateway\Contracts\Services\WebhookServiceContract;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Enums\InboundMode;
use JOOservices\LaravelChatGateway\Enums\WebhookEventStatus;
use JOOservices\LaravelChatGateway\Events\WebhookDeduplicated;
use JOOservices\LaravelChatGateway\Events\WebhookReceived;
use JOOservices\LaravelChatGateway\Events\WebhookRejected;
use JOOservices\LaravelChatGateway\Events\WebhookVerified;
use JOOservices\LaravelChatGateway\Exceptions\WebhookRejectedException;
use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;

final class WebhookService implements WebhookServiceContract
{
    public function __construct(
        private readonly ChannelServiceContract $channelService,
        private readonly ChatWebhookEventRepositoryContract $webhookEventRepository,
        private readonly InboundIngestionServiceContract $inboundIngestionService,
        private readonly InboundModeResolverContract $inboundModeResolver,
        private readonly ChatGatewayManager $manager,
        private readonly Dispatcher $events,
        private readonly CacheFactory $cacheFactory,
    ) {}

    public function process(Request $request, string $provider, ?string $channelKey = null): ChatWebhookEvent
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();
        /** @var array<string, mixed> $headers */
        $headers = array_map(static fn (array $values): string => implode(',', $values), $request->headers->all());
        $channel = $this->channelService->resolveInbound($provider, $channelKey, $payload, $headers);

        if ($this->inboundModeResolver->resolve($channel) !== InboundMode::Callback || ! $this->inboundModeResolver->isWebhookEnabled($channel)) {
            throw new WebhookRejectedException('Webhook mode is not enabled for this channel.');
        }

        $providerInstance = $this->manager->providerForChannel($channel);
        $parsed = $providerInstance->parser()->parse($payload, $headers, $channel);
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $verification = $providerInstance->verify($request, $channel);

        if (! $verification->verified) {
            $webhookEvent = $this->webhookEventRepository->createReceived(
                $channel,
                $provider,
                'callback',
                $parsed->externalEventId,
                $parsed->eventType,
                $payload,
                config('chat-gateway.webhooks.store_headers', true) ? $headers : null,
                $payloadHash,
            );
            $webhookEvent = $this->webhookEventRepository->markStatus($webhookEvent, WebhookEventStatus::Rejected->value, $verification->reason);
            $this->events->dispatch(new WebhookRejected($webhookEvent, $verification->reason ?? 'Webhook verification failed.'));

            throw new WebhookRejectedException($verification->reason ?? 'Webhook verification failed.');
        }

        $duplicate = $this->webhookEventRepository->findDuplicate($provider, $parsed->externalEventId, $payloadHash);
        $duplicateByCache = $this->dedupeKeyAlreadySeen($provider, $parsed->externalEventId, $payloadHash);

        if ($duplicate !== null || $duplicateByCache) {
            $existing = $duplicate;

            if ($existing === null) {
                $existing = $this->webhookEventRepository->createReceived(
                    $channel,
                    $provider,
                    'callback',
                    $parsed->externalEventId,
                    $parsed->eventType,
                    $payload,
                    config('chat-gateway.webhooks.store_headers', true) ? $headers : null,
                    $payloadHash,
                );
            }

            $existing = $this->webhookEventRepository->markStatus($existing, WebhookEventStatus::Deduplicated->value, 'Duplicate webhook event.');
            $this->events->dispatch(new WebhookDeduplicated($existing));

            return $existing;
        }

        $this->storeDedupeKey($provider, $parsed->externalEventId, $payloadHash);

        $webhookEvent = $this->webhookEventRepository->createReceived(
            $channel,
            $provider,
            'callback',
            $parsed->externalEventId,
            $parsed->eventType,
            $payload,
            config('chat-gateway.webhooks.store_headers', true) ? $headers : null,
            $payloadHash,
        );

        $this->events->dispatch(new WebhookReceived($webhookEvent, $payload));

        $webhookEvent = $this->webhookEventRepository->markStatus($webhookEvent, WebhookEventStatus::Verified->value);
        $this->events->dispatch(new WebhookVerified($webhookEvent));

        $this->inboundIngestionService->ingest(
            $provider,
            $channel,
            $parsed,
            config('chat-gateway.webhooks.persist_raw_payload', false) ? $payload : null,
        );

        return $this->webhookEventRepository->markProcessed($webhookEvent);
    }

    public function verify(Request $request, string $provider, ?string $channelKey = null): VerificationResultDto
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();
        /** @var array<string, mixed> $headers */
        $headers = array_map(static fn (array $values): string => implode(',', $values), $request->headers->all());
        $channel = $this->channelService->resolveInbound($provider, $channelKey, $payload, $headers);

        if ($this->inboundModeResolver->resolve($channel) !== InboundMode::Callback || ! $this->inboundModeResolver->isWebhookEnabled($channel)) {
            throw new WebhookRejectedException('Webhook mode is not enabled for this channel.');
        }

        return $this->manager->providerForChannel($channel)->verify($request, $channel);
    }

    private function dedupeKeyAlreadySeen(string $provider, ?string $externalEventId, string $payloadHash): bool
    {
        $cacheStore = $this->cacheFactory->store((string) config('chat-gateway.cache.store', 'array'));
        $identifier = $externalEventId !== null && $externalEventId !== '' ? $externalEventId : $payloadHash;
        $cacheKey = sprintf('%s:webhook_dedupe:%s:%s', (string) config('chat-gateway.cache.prefix', 'chat_gateway'), $provider, $identifier);

        return $cacheStore->has($cacheKey);
    }

    private function storeDedupeKey(string $provider, ?string $externalEventId, string $payloadHash): void
    {
        $cacheStore = $this->cacheFactory->store((string) config('chat-gateway.cache.store', 'array'));
        $identifier = $externalEventId !== null && $externalEventId !== '' ? $externalEventId : $payloadHash;
        $cacheKey = sprintf('%s:webhook_dedupe:%s:%s', (string) config('chat-gateway.cache.prefix', 'chat_gateway'), $provider, $identifier);

        $cacheStore->put($cacheKey, true, (int) config('chat-gateway.cache.webhook_dedupe_ttl_seconds', 300));
    }
}
