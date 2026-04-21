<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Services;

use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Contracts\ResponseWrapperInterface;
use JOOservices\Client\Exceptions\NetworkConnectionException;
use InvalidArgumentException;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundIngestionServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\PollingServiceContract;
use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramProvider;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramUpdateFetcher;
use JOOservices\LaravelChatGateway\Services\ChatGatewayManager;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;
use RuntimeException;

final class PollingServiceTest extends TestCase
{
    public function test_poll_mode_is_the_default_config(): void
    {
        $this->assertSame('poll', config('chat-gateway.inbound.default_mode'));
        $this->assertSame('poll', config('chat-gateway.providers.telegram.inbound_mode'));
    }

    public function test_it_polls_telegram_updates_and_persists_operational_records(): void
    {
        $channel = $this->makePollingChannel();
        $this->mockGetUpdatesFixture('telegram/poll_get_updates_text.json', 30);

        $result = $this->app->make(PollingServiceContract::class)->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key));

        $this->assertSame(1, $result->fetchedCount);
        $this->assertSame(1, $result->processedCount);
        $this->assertSame(1002, $result->offset);
        $this->assertDatabaseHas('chat_polling_states', ['provider' => 'telegram', 'channel_id' => $channel->id, 'offset' => 1002]);
        $this->assertDatabaseHas('chat_messages', ['provider' => 'telegram', 'direction' => 'inbound', 'external_message_id' => '11']);
        $this->assertDatabaseHas('chat_webhook_events', ['provider' => 'telegram', 'transport' => 'poll', 'external_event_id' => '1001', 'status' => 'processed']);
    }

    public function test_it_advances_offset_only_after_successful_processing(): void
    {
        $channel = $this->makePollingChannel();
        $this->mockGetUpdatesFixture('telegram/poll_get_updates_text.json', 30);

        $ingestion = Mockery::mock(InboundIngestionServiceContract::class);
        $ingestion->shouldReceive('ingest')->once()->andThrow(new RuntimeException('boom'));
        $this->app->instance(InboundIngestionServiceContract::class, $ingestion);
        $this->app->forgetInstance(PollingServiceContract::class);

        try {
            $this->app->make(PollingServiceContract::class)->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key));
            $this->fail('Expected polling to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertDatabaseHas('chat_polling_states', ['provider' => 'telegram', 'channel_id' => $channel->id, 'offset' => 0]);
    }

    public function test_it_deduplicates_polled_updates_without_creating_duplicate_messages(): void
    {
        $channel = $this->makePollingChannel();
        $this->mockGetUpdatesFixture('telegram/poll_get_updates_text.json', 30, 2);

        $service = $this->app->make(PollingServiceContract::class);
        $first = $service->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key));
        $second = $service->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key));

        $this->assertSame(1, $first->processedCount);
        $this->assertSame(1, $second->deduplicatedCount);
        $this->assertDatabaseCount('chat_messages', 1);
    }

    public function test_it_treats_an_empty_telegram_poll_batch_as_a_clean_idle_result(): void
    {
        $channel = $this->makePollingChannel();
        $this->mockGetUpdatesFixture('telegram/poll_get_updates_empty.json', 30);

        $result = $this->app->make(PollingServiceContract::class)->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key));

        $this->assertSame(0, $result->fetchedCount);
        $this->assertSame(0, $result->processedCount);
        $this->assertSame(0, $result->failedCount);
        $this->assertSame(0, $result->offset);
        $this->assertDatabaseHas('chat_polling_states', ['provider' => 'telegram', 'channel_id' => $channel->id, 'offset' => 0]);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_it_uses_an_http_timeout_larger_than_the_telegram_poll_timeout(): void
    {
        $channel = $this->makePollingChannel();
        $this->mockGetUpdatesFixture('telegram/poll_get_updates_empty.json', 30);

        $this->app->make(PollingServiceContract::class)->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key, timeout: 30));

        $this->assertDatabaseHas('chat_polling_states', ['provider' => 'telegram', 'channel_id' => $channel->id, 'offset' => 0]);
    }

    public function test_it_keeps_real_transport_failures_fatal(): void
    {
        $channel = $this->makePollingChannel();
        $this->mockTransportException(new NetworkConnectionException('network down'));

        $this->expectException(NetworkConnectionException::class);
        $this->expectExceptionMessage('network down');

        $this->app->make(PollingServiceContract::class)->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key));
    }

    public function test_it_throws_when_the_telegram_channel_has_no_bot_token(): void
    {
        $channel = $this->makePollingChannel(channelKey: 'telegram-missing-token', token: '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Telegram bot_token credential is missing for channel [telegram-missing-token].');

        $this->app->make(PollingServiceContract::class)->poll('telegram', new PollingBatchOptionsDto(channelKey: $channel->channel_key));
    }

    public function test_it_uses_channel_aware_dedupe_for_polled_updates(): void
    {
        $firstChannel = $this->makePollingChannel(channelKey: 'telegram-one', token: 'bot-token-1');
        $secondChannel = $this->makePollingChannel(channelKey: 'telegram-two', token: 'bot-token-2', isDefault: false);
        $payload = $this->loadJsonFixture('telegram/poll_get_updates_text.json');
        $secondPayload = $this->loadJsonFixture('telegram/poll_get_updates_text.json');
        $secondPayload['result'][0]['message']['message_id'] = 12;

        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $responseOne = Mockery::mock(ResponseWrapperInterface::class);
        $responseTwo = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->twice()->andReturn($client);
        $client->shouldReceive('post')->once()->with('/botbot-token-1/getUpdates', Mockery::type('array'))->andReturn($responseOne);
        $client->shouldReceive('post')->once()->with('/botbot-token-2/getUpdates', Mockery::type('array'))->andReturn($responseTwo);
        $responseOne->shouldReceive('json')->once()->andReturn($payload);
        $responseTwo->shouldReceive('json')->once()->andReturn($secondPayload);
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $this->app->forgetInstance(PollingServiceContract::class);
        $this->app->forgetInstance(ChatGatewayManager::class);

        $service = $this->app->make(PollingServiceContract::class);
        $first = $service->poll('telegram', new PollingBatchOptionsDto(channelKey: $firstChannel->channel_key));
        $second = $service->poll('telegram', new PollingBatchOptionsDto(channelKey: $secondChannel->channel_key));

        $this->assertSame(1, $first->processedCount);
        $this->assertSame(1, $second->processedCount);
        $this->assertSame(0, $second->deduplicatedCount);
        $this->assertDatabaseHas('chat_webhook_events', ['channel_id' => $firstChannel->id, 'external_event_id' => '1001', 'status' => 'processed']);
        $this->assertDatabaseHas('chat_webhook_events', ['channel_id' => $secondChannel->id, 'external_event_id' => '1001', 'status' => 'processed']);
    }

    private function makePollingChannel(string $channelKey = 'telegram-default', string $token = 'bot-token', bool $isDefault = true): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => $channelKey,
            'name' => 'Telegram',
            'status' => 'active',
            'is_default' => $isDefault,
            'credentials' => $token !== '' ? ['bot_token' => $token] : [],
            'settings' => [
                'inbound_mode' => 'poll',
                'polling' => ['enabled' => true],
                'webhook' => ['enabled' => false],
            ],
            'webhook_secret' => 'telegram-secret',
        ]);
    }

    private function mockGetUpdatesFixture(string $fixture, int $pollTimeout, int $requestCount = 1): void
    {
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $response = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->andReturn($client);
        $client->shouldReceive('post')
            ->times($requestCount)
            ->with('/botbot-token/getUpdates', Mockery::on(function (array $options) use ($pollTimeout): bool {
                return ($options['timeout'] ?? null) === $pollTimeout + 5
                    && ($options['connect_timeout'] ?? null) === 5
                    && ($options['json']['timeout'] ?? null) === $pollTimeout;
            }))
            ->andReturn($response);
        $response->shouldReceive('json')->times($requestCount)->andReturn($this->loadJsonFixture($fixture));
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $this->app->forgetInstance(TelegramUpdateFetcher::class);
        $this->app->forgetInstance(TelegramProvider::class);
        $this->app->forgetInstance(ChatGatewayManager::class);
        $this->app->forgetInstance(PollingServiceContract::class);
    }

    private function mockTransportException(\Throwable $exception): void
    {
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);

        $factory->shouldReceive('make')->andReturn($client);
        $client->shouldReceive('post')->once()->andThrow($exception);
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $this->app->forgetInstance(TelegramUpdateFetcher::class);
        $this->app->forgetInstance(TelegramProvider::class);
        $this->app->forgetInstance(ChatGatewayManager::class);
        $this->app->forgetInstance(PollingServiceContract::class);
    }
}