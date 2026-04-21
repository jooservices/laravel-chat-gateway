<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Telegram;

use InvalidArgumentException;
use JOOservices\LaravelChatGateway\Contracts\Providers\PollingUpdateFetcherContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\DTOs\PollingFetchResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatPollingState;

final class TelegramUpdateFetcher implements PollingUpdateFetcherContract
{
    public function __construct(
        private readonly ProviderHttpClientFactoryContract $clientFactory,
    ) {}

    public function fetch(ChatChannel $channel, ChatPollingState $state, PollingBatchOptionsDto $options): PollingFetchResultDto
    {
        $token = (string) ($channel->credentials['bot_token'] ?? '');

        if ($token === '') {
            throw new InvalidArgumentException('Telegram bot_token credential is missing for channel ['.$channel->channel_key.'].');
        }

        $pollTimeout = max(1, (int) ($options->timeout ?? config('chat-gateway.providers.telegram.polling.timeout', 30)));
        $httpTimeout = max(
            (int) config('chat-gateway.providers.telegram.timeout', 15),
            $pollTimeout + 5,
        );
        $connectTimeout = min(
            (int) config('chat-gateway.providers.telegram.connect_timeout', 5),
            max(1, $httpTimeout - 1),
        );

        $response = $this->clientFactory->make($channel)->post('/bot'.$token.'/getUpdates', [
            'timeout' => $httpTimeout,
            'connect_timeout' => $connectTimeout,
            'json' => [
                'offset' => $state->offset,
                'limit' => $options->limit ?? 100,
                'timeout' => $pollTimeout,
                'allowed_updates' => $options->allowedUpdates ?? [],
            ],
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        /** @var list<array<string, mixed>> $updates */
        $updates = data_get($payload, 'result', []);

        return new PollingFetchResultDto($updates);
    }
}