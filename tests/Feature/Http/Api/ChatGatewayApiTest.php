<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Http\Api;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use JOOservices\LaravelChatGateway\Jobs\DispatchChatMessageJob;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelChatGateway\Models\ChatMessageStatusLog;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ChatGatewayApiTest extends TestCase
{
    public function test_api_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('api.v1.chat-gateway.webhooks.telegram'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.webhooks.whatsapp'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.webhooks.viber'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.channels.index'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.channels.store'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.channels.show'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.channels.update'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.messages.store'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.messages.show'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.messages.retry'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.conversations.index'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.conversations.show'));
        $this->assertTrue(Route::has('api.v1.chat-gateway.conversations.messages'));

        $this->assertSame('/api/v1/chat-gateway/messages', route('api.v1.chat-gateway.messages.store', [], false));
    }

    public function test_webhook_route_processes_telegram_request(): void
    {
        ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => 'telegram-api',
            'name' => 'Telegram API',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['bot_token' => 'bot-token'],
            'settings' => [
                'inbound_mode' => 'callback',
                'webhook' => ['enabled' => true],
            ],
            'webhook_secret' => 'telegram-secret',
        ]);

        $response = $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'])
            ->postJson('/api/v1/chat-gateway/webhooks/telegram', $this->loadJsonFixture('telegram/inbound_text.json'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'processed');
    }

    public function test_store_message_request_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/chat-gateway/messages', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['provider', 'channel_key', 'external_chat_id', 'type']);
    }

    public function test_store_channel_request_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/chat-gateway/channels', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['provider', 'channel_key', 'name', 'credentials']);
    }

    public function test_update_channel_request_allows_partial_updates(): void
    {
        $channel = $this->makeChannel(channelKey: 'telegram-partial', isDefault: false);

        $response = $this->patchJson('/api/v1/chat-gateway/channels/'.$channel->id, [
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'inactive');
        $this->assertDatabaseHas('chat_channels', [
            'id' => $channel->id,
            'status' => 'inactive',
        ]);
    }

    public function test_unsupported_provider_produces_clear_api_error(): void
    {
        $response = $this->postJson('/api/v1/chat-gateway/messages', [
            'provider' => 'foo',
            'channel_key' => 'foo-channel',
            'external_chat_id' => 'chat-1',
            'type' => 'text',
            'content' => 'Hello',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Unsupported provider [foo].',
        ]);
    }

    public function test_post_messages_creates_outbound_message_and_queues_send(): void
    {
        Queue::fake();

        config()->set('chat-gateway.queue.enabled', true);
        config()->set('chat-gateway.queue.connection', 'redis');
        config()->set('chat-gateway.queue.queues.outbound', 'chat-outbound');

        $channel = $this->makeChannel(channelKey: 'telegram-outbound-api');

        $response = $this->postJson('/api/v1/chat-gateway/messages', [
            'provider' => $channel->provider,
            'channel_key' => $channel->channel_key,
            'external_chat_id' => 'tg-chat-api',
            'type' => 'text',
            'content' => 'Outbound API hello',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'queued');
        $this->assertDatabaseHas('chat_messages', [
            'direction' => 'outbound',
            'status' => 'queued',
            'content' => 'Outbound API hello',
        ]);
        Queue::assertPushed(DispatchChatMessageJob::class, 1);
    }

    public function test_get_message_returns_status_in_payload(): void
    {
        $conversation = $this->makeConversation();
        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'provider' => 'telegram',
            'direction' => 'outbound',
            'type' => 'text',
            'status' => 'sent',
            'external_message_id' => 'provider-msg-1',
            'content' => 'Status payload hello',
        ]);

        ChatMessageStatusLog::query()->create([
            'message_id' => $message->id,
            'old_status' => 'queued',
            'new_status' => 'sent',
            'provider_status' => 'provider-sent',
            'payload' => ['ok' => true],
        ]);

        $response = $this->getJson('/api/v1/chat-gateway/messages/'.$message->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $message->id);
        $response->assertJsonPath('data.status', 'sent');
        $response->assertJsonPath('data.provider_status', 'provider-sent');
        $response->assertJsonPath('data.external_message_id', 'provider-msg-1');
    }

    public function test_post_message_retry_requeues_message(): void
    {
        Queue::fake();

        config()->set('chat-gateway.queue.connection', 'redis');
        config()->set('chat-gateway.queue.queues.outbound', 'chat-outbound');

        $conversation = $this->makeConversation();
        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'provider' => 'telegram',
            'direction' => 'outbound',
            'type' => 'text',
            'status' => 'failed',
            'content' => 'Retry me',
            'error_message' => 'Temporary failure',
            'normalized_payload' => [
                'conversationId' => $conversation->id,
                'channelId' => $conversation->channel_id,
                'channelKey' => $conversation->channel->channel_key,
                'externalChatId' => $conversation->external_chat_id,
                'type' => 'text',
                'content' => 'Retry me',
                'attachments' => [],
                'replyToMessageId' => null,
                'meta' => null,
            ],
        ]);

        $response = $this->postJson('/api/v1/chat-gateway/messages/'.$message->id.'/retry');

        $response->assertStatus(202);
        $response->assertJsonPath('data.status', 'queued');
        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'status' => 'queued',
            'error_message' => null,
        ]);
        Queue::assertPushed(DispatchChatMessageJob::class, 1);
    }

    public function test_post_channels_creates_channel_through_service(): void
    {
        $response = $this->postJson('/api/v1/chat-gateway/channels', [
            'provider' => 'telegram',
            'channel_key' => 'telegram-create-api',
            'name' => 'Telegram Create API',
            'credentials' => ['bot_token' => 'bot-token-create'],
            'webhook_secret' => 'webhook-secret',
            'settings' => [
                'inbound_mode' => 'callback',
                'webhook' => ['enabled' => true],
            ],
            'status' => 'active',
            'is_default' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.channel_key', 'telegram-create-api');
        $this->assertDatabaseHas('chat_channels', [
            'provider' => 'telegram',
            'channel_key' => 'telegram-create-api',
            'is_default' => true,
        ]);
    }

    public function test_patch_channel_updates_status_and_is_default_via_payload(): void
    {
        $first = $this->makeChannel(channelKey: 'telegram-default-api', isDefault: true);
        $second = $this->makeChannel(channelKey: 'telegram-secondary-api', isDefault: false);

        $response = $this->patchJson('/api/v1/chat-gateway/channels/'.$second->id, [
            'status' => 'inactive',
            'is_default' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'inactive');
        $response->assertJsonPath('data.is_default', true);
        $this->assertFalse((bool) $first->fresh()->is_default);
    }

    public function test_get_channels_and_show_channel_work(): void
    {
        $channel = $this->makeChannel(channelKey: 'telegram-read-api');

        $indexResponse = $this->getJson('/api/v1/chat-gateway/channels');
        $showResponse = $this->getJson('/api/v1/chat-gateway/channels/'.$channel->id);

        $indexResponse->assertOk();
        $indexResponse->assertJsonPath('data.0.channel_key', 'telegram-read-api');
        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.id', $channel->id);
    }

    public function test_get_conversations_works(): void
    {
        $conversation = $this->makeConversation();

        $indexResponse = $this->getJson('/api/v1/chat-gateway/conversations');
        $showResponse = $this->getJson('/api/v1/chat-gateway/conversations/'.$conversation->id);

        $indexResponse->assertOk();
        $indexResponse->assertJsonPath('data.0.external_chat_id', 'tg-chat-1');
        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.id', $conversation->id);
    }

    public function test_get_conversation_messages_works(): void
    {
        $conversation = $this->makeConversation();
        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'provider' => 'telegram',
            'direction' => 'inbound',
            'type' => 'text',
            'status' => 'sent',
            'external_message_id' => 'inbound-msg-1',
            'content' => 'Inbound hello',
        ]);

        $response = $this->getJson('/api/v1/chat-gateway/conversations/'.$conversation->id.'/messages');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $message->id);
        $response->assertJsonPath('data.0.status', 'sent');
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

    private function makeChannel(string $channelKey = 'telegram-api-default', bool $isDefault = true): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => $channelKey,
            'name' => 'Telegram API',
            'status' => 'active',
            'is_default' => $isDefault,
            'credentials' => ['bot_token' => 'bot-token'],
            'settings' => [
                'inbound_mode' => 'callback',
                'webhook' => ['enabled' => true],
            ],
            'webhook_secret' => 'telegram-secret',
        ]);
    }
}