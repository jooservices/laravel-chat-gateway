<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Providers;

use Illuminate\Http\Request;
use InvalidArgumentException;
use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Contracts\ResponseWrapperInterface;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\DTOs\AttachmentDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatPollingState;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramProvider;
use JOOservices\LaravelChatGateway\Services\ChatGatewayManager;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;

final class TelegramProviderTest extends TestCase
{
    public function test_it_parses_text_media_and_callback_fixtures(): void
    {
        $provider = $this->app->make(TelegramProvider::class);
        $channel = $this->makeChannel('telegram', 'telegram-default');

        $text = $provider->parser()->parse($this->loadJsonFixture('telegram/inbound_text.json'), [], $channel);
        $media = $provider->parser()->parse($this->loadJsonFixture('telegram/inbound_media.json'), [], $channel);
        $callback = $provider->parser()->parse($this->loadJsonFixture('telegram/callback_interactive.json'), [], $channel);
        $system = $provider->parser()->parse($this->loadJsonFixture('telegram/malformed_payload.json'), [], $channel);

        $this->assertTrue($text->isMessageEvent);
        $this->assertSame('text', $text->messageType);
        $this->assertSame('Hello telegram', $text->content);
        $this->assertSame('image', $media->messageType);
        $this->assertCount(1, $media->attachments);
        $this->assertTrue($callback->isInteractionEvent);
        $this->assertSame('callback_action', $callback->eventType);
        $this->assertSame('system', $system->eventType);
    }

    public function test_it_verifies_telegram_secret(): void
    {
        $provider = $this->app->make(TelegramProvider::class);
        $channel = $this->makeChannel('telegram', 'telegram-default');

        $request = Request::create('/hook', 'POST', server: ['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'telegram-secret']);

        $this->assertTrue($provider->verify($request, $channel)->verified);
    }

    public function test_it_rejects_telegram_secret_when_channel_secret_is_empty(): void
    {
        $provider = $this->app->make(TelegramProvider::class);
        $channel = $this->makeChannel('telegram', 'telegram-empty-secret', webhookSecret: '');
        $request = Request::create('/hook', 'POST', server: ['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => '']);

        $result = $provider->verify($request, $channel);

        $this->assertFalse($result->verified);
        $this->assertSame('Channel has no webhook secret configured.', $result->reason);
    }

    public function test_it_maps_telegram_chat_origin_fields(): void
    {
        $provider = $this->app->make(TelegramProvider::class);
        $channel = $this->makeChannel('telegram', 'telegram-default');

        $payload = [
            'update_id' => 2001,
            'message' => [
                'message_id' => 21,
                'date' => 1713686400,
                'chat' => [
                    'id' => 'tg-group-1',
                    'type' => 'supergroup',
                    'title' => 'Support Room',
                    'username' => 'support_room',
                ],
                'from' => [
                    'id' => 'tg-user-1',
                    'username' => 'alice',
                    'first_name' => 'Alice',
                ],
                'text' => 'Hello telegram',
            ],
        ];

        $message = $provider->parser()->parse($payload, [], $channel);

        $this->assertSame('supergroup', $message->conversation->chatType);
        $this->assertSame('Support Room', $message->conversation->chatTitle);
        $this->assertSame('support_room', $message->conversation->chatUsername);
    }

    public function test_it_sends_messages_through_mocked_client_factory(): void
    {
        $channel = $this->makeChannel('telegram', 'telegram-default');
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $response = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->once()->andReturn($client);
        $client->shouldReceive('post')->once()->andReturn($response);
        $response->shouldReceive('json')->once()->andReturn($this->loadJsonFixture('telegram/outbound_success.json'));
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $this->app->forgetInstance(TelegramProvider::class);
        $this->app->forgetInstance(ChatGatewayManager::class);
        $provider = $this->app->make(TelegramProvider::class);

        $outbound = new OutboundMessageDto(
            conversationId: null,
            channelId: $channel->id,
            channelKey: $channel->channel_key,
            externalChatId: 'tg-chat-1',
            type: 'image',
            content: 'Caption',
            attachments: [new AttachmentDto(type: 'image', url: 'https://example.com/image.jpg')],
        );

        $result = $provider->send($channel, $outbound);

        $this->assertTrue($result->successful);
        $this->assertSame('sent', $result->status);
        $this->assertSame('9001', $result->externalMessageId);
    }

    public function test_it_fetches_telegram_updates_through_mocked_client_factory(): void
    {
        $channel = $this->makeChannel('telegram', 'telegram-default');
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $response = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->once()->andReturn($client);
        $client->shouldReceive('post')->once()->andReturn($response);
        $response->shouldReceive('json')->once()->andReturn($this->loadJsonFixture('telegram/poll_get_updates_text.json'));
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $this->app->forgetInstance(TelegramProvider::class);
        $this->app->forgetInstance(ChatGatewayManager::class);
        $provider = $this->app->make(TelegramProvider::class);

        $result = $provider->pollingFetcher()->fetch(
            $channel,
            new ChatPollingState(['provider' => 'telegram', 'channel_id' => $channel->id, 'offset' => 1001]),
            new PollingBatchOptionsDto(timeout: 30, limit: 100),
        );

        $this->assertCount(1, $result->updates);
        $this->assertSame(1001, $result->updates[0]['update_id']);
    }

    public function test_it_throws_when_telegram_bot_token_is_missing_for_polling(): void
    {
        $provider = $this->app->make(TelegramProvider::class);
        $channel = $this->makeChannel('telegram', 'telegram-missing-token', credentials: []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Telegram bot_token credential is missing for channel [telegram-missing-token].');

        $provider->pollingFetcher()->fetch(
            $channel,
            new ChatPollingState(['provider' => 'telegram', 'channel_id' => $channel->id, 'offset' => 1001]),
            new PollingBatchOptionsDto(timeout: 30, limit: 100),
        );
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function makeChannel(string $provider, string $channelKey, array $credentials = ['bot_token' => 'bot-token'], string $webhookSecret = 'telegram-secret'): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => $provider,
            'channel_key' => $channelKey,
            'name' => ucfirst($provider),
            'status' => 'active',
            'is_default' => true,
            'credentials' => $credentials,
            'settings' => [],
            'webhook_secret' => $webhookSecret,
        ]);
    }
}
