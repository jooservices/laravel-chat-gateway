<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Providers;

use Illuminate\Http\Request;
use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Contracts\ResponseWrapperInterface;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Providers\Viber\ViberProvider;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;

final class ViberProviderTest extends TestCase
{
    public function test_it_parses_viber_message_callback_and_status_fixtures(): void
    {
        $provider = $this->app->make(ViberProvider::class);
        $channel = $this->makeChannel();

        $text = $provider->parser()->parse($this->loadJsonFixture('viber/inbound_text.json'), [], $channel);
        $callback = $provider->parser()->parse($this->loadJsonFixture('viber/callback_interactive.json'), [], $channel);
        $statusFixtures = $this->loadJsonFixture('viber/delivery_read_status.json');
        $delivered = $provider->parser()->parse($statusFixtures['delivered'], [], $channel);
        $read = $provider->parser()->parse($statusFixtures['seen'], [], $channel);

        $this->assertTrue($text->isMessageEvent);
        $this->assertTrue($callback->isInteractionEvent);
        $this->assertTrue($delivered->isStatusEvent);
        $this->assertSame('delivery_status', $delivered->eventType);
        $this->assertSame('read_status', $read->eventType);
    }

    public function test_it_verifies_viber_signature(): void
    {
        $provider = $this->app->make(ViberProvider::class);
        $channel = $this->makeChannel();
        $content = $this->loadFixture('viber/inbound_text.json');
        $signature = hash_hmac('sha256', $content, 'viber-secret');
        $request = Request::create('/hook', 'POST', [], [], [], ['HTTP_X_VIBER_CONTENT_SIGNATURE' => $signature], $content);

        $this->assertTrue($provider->verify($request, $channel)->verified);
    }

    public function test_it_sends_viber_messages_through_mocked_client_factory(): void
    {
        $channel = $this->makeChannel();
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $response = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->once()->andReturn($client);
        $client->shouldReceive('post')->once()->andReturn($response);
        $response->shouldReceive('json')->once()->andReturn($this->loadJsonFixture('viber/outbound_success.json'));
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $provider = $this->app->make(ViberProvider::class);

        $result = $provider->send($channel, new OutboundMessageDto(
            conversationId: null,
            channelId: $channel->id,
            channelKey: $channel->channel_key,
            externalChatId: 'vb-user-1',
            type: 'text',
            content: 'Hello Viber'
        ));

        $this->assertTrue($result->successful);
        $this->assertSame('sent', $result->status);
    }

    private function makeChannel(): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => 'viber',
            'channel_key' => 'viber-default',
            'name' => 'Viber',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['auth_token' => 'viber-token'],
            'settings' => [],
            'webhook_secret' => 'viber-secret',
        ]);
    }
}
