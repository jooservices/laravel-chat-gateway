<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Providers;

use Illuminate\Http\Request;
use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Contracts\ResponseWrapperInterface;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Providers\WhatsApp\WhatsAppProvider;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;

final class WhatsAppProviderTest extends TestCase
{
    public function test_it_parses_whatsapp_message_interaction_and_status_fixtures(): void
    {
        $provider = $this->app->make(WhatsAppProvider::class);
        $channel = $this->makeChannel();

        $text = $provider->parser()->parse($this->loadJsonFixture('whatsapp/inbound_text.json'), [], $channel);
        $interactive = $provider->parser()->parse($this->loadJsonFixture('whatsapp/callback_interactive.json'), [], $channel);
        $statusFixtures = $this->loadJsonFixture('whatsapp/delivery_read_status.json');
        $delivered = $provider->parser()->parse($statusFixtures['delivered'], [], $channel);
        $read = $provider->parser()->parse($statusFixtures['read'], [], $channel);

        $this->assertTrue($text->isMessageEvent);
        $this->assertTrue($interactive->isInteractionEvent);
        $this->assertSame('delivery_status', $delivered->eventType);
        $this->assertSame('read_status', $read->eventType);
    }

    public function test_it_verifies_whatsapp_get_challenge(): void
    {
        $provider = $this->app->make(WhatsAppProvider::class);
        $channel = $this->makeChannel();
        $request = Request::create('/verify', 'GET', ['hub_verify_token' => 'wa-secret', 'hub_challenge' => '12345']);

        $result = $provider->verify($request, $channel);

        $this->assertTrue($result->verified);
        $this->assertSame('12345', $result->challenge);
    }

    public function test_it_sends_whatsapp_messages_through_mocked_client_factory(): void
    {
        $channel = $this->makeChannel();
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $response = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->once()->andReturn($client);
        $client->shouldReceive('post')->once()->andReturn($response);
        $response->shouldReceive('json')->once()->andReturn($this->loadJsonFixture('whatsapp/outbound_success.json'));
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $provider = $this->app->make(WhatsAppProvider::class);

        $result = $provider->send($channel, new OutboundMessageDto(
            conversationId: null,
            channelId: $channel->id,
            channelKey: $channel->channel_key,
            externalChatId: '84909999999',
            type: 'text',
            content: 'Hello WhatsApp'
        ));

        $this->assertTrue($result->successful);
        $this->assertSame('sent', $result->status);
        $this->assertSame('wamid.outbound.1', $result->externalMessageId);
    }

    private function makeChannel(): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => 'whatsapp',
            'channel_key' => 'wa-channel-1',
            'name' => 'WhatsApp',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['access_token' => 'wa-token', 'phone_number_id' => '1234567890', 'app_secret' => 'wa-app-secret'],
            'settings' => [],
            'webhook_secret' => 'wa-secret',
        ]);
    }
}
