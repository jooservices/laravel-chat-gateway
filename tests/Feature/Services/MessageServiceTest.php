<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Services;

use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Contracts\ResponseWrapperInterface;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramProvider;
use JOOservices\LaravelChatGateway\Services\ChatGatewayManager;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;

final class MessageServiceTest extends TestCase
{
    public function test_it_creates_and_sends_outbound_message_and_status_log(): void
    {
        $conversation = $this->makeConversation();
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $response = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->once()->andReturn($client);
        $client->shouldReceive('post')->once()->andReturn($response);
        $response->shouldReceive('json')->once()->andReturn($this->loadJsonFixture('telegram/outbound_success.json'));
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $this->app->forgetInstance(TelegramProvider::class);
        $this->app->forgetInstance(ChatGatewayManager::class);
        $this->app->forgetInstance(MessageServiceContract::class);

        $result = $this->app->make(MessageServiceContract::class)->send(new OutboundMessageDto(
            conversationId: $conversation->id,
            channelId: null,
            channelKey: null,
            externalChatId: null,
            type: 'text',
            content: 'Outbound hello'
        ));

        $this->assertTrue($result->successful);
        $this->assertDatabaseHas('chat_messages', ['direction' => 'outbound', 'status' => 'sent']);
        $this->assertDatabaseCount('chat_message_status_logs', 1);
    }

    public function test_it_bootstraps_a_conversation_for_outbound_send_when_only_chat_id_is_provided(): void
    {
        $channel = $this->makeChannel();
        $factory = Mockery::mock(ProviderHttpClientFactoryContract::class);
        $client = Mockery::mock(HttpClientInterface::class);
        $response = Mockery::mock(ResponseWrapperInterface::class);

        $factory->shouldReceive('make')->once()->andReturn($client);
        $client->shouldReceive('post')->once()->andReturn($response);
        $response->shouldReceive('json')->once()->andReturn($this->loadJsonFixture('telegram/outbound_success.json'));
        $this->app->instance(ProviderHttpClientFactoryContract::class, $factory);
        $this->app->forgetInstance(TelegramProvider::class);
        $this->app->forgetInstance(ChatGatewayManager::class);
        $this->app->forgetInstance(MessageServiceContract::class);

        $result = $this->app->make(MessageServiceContract::class)->send(new OutboundMessageDto(
            conversationId: null,
            channelId: null,
            channelKey: $channel->channel_key,
            externalChatId: '-5189484920',
            type: 'text',
            content: 'Outbound bootstrap hello',
        ));

        $this->assertTrue($result->successful);
        $this->assertDatabaseHas('chat_conversations', ['channel_id' => $channel->id, 'external_chat_id' => '-5189484920']);
        $this->assertDatabaseHas('chat_messages', ['direction' => 'outbound', 'status' => 'sent', 'content' => 'Outbound bootstrap hello']);
    }

    public function test_it_updates_message_status(): void
    {
        $conversation = $this->makeConversation();
        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'provider' => 'telegram',
            'direction' => 'outbound',
            'type' => 'text',
            'status' => 'sent',
            'external_message_id' => 'msg-1',
        ]);

        $updated = $this->app->make(MessageServiceContract::class)->updateStatus($message, 'delivered', 'provider-delivered', ['foo' => 'bar']);

        $this->assertSame('delivered', $updated->status);
        $this->assertDatabaseHas('chat_message_status_logs', ['message_id' => $message->id, 'new_status' => 'delivered']);
    }

    private function makeConversation(): ChatConversation
    {
        $channel = $this->makeChannel();

        $contact = ChatContact::query()->create([
            'provider' => 'telegram',
            'channel_id' => $channel->id,
            'external_contact_id' => 'tg-user-1',
        ]);

        return ChatConversation::query()->create([
            'channel_id' => $channel->id,
            'contact_id' => $contact->id,
            'external_chat_id' => 'tg-chat-1',
            'status' => 'open',
        ]);
    }

    private function makeChannel(): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => 'telegram-default',
            'name' => 'Telegram',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['bot_token' => 'bot-token'],
            'settings' => [],
            'webhook_secret' => 'telegram-secret',
        ]);
    }
}
