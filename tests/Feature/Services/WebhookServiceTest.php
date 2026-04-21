<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Services;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\Contracts\Services\WebhookServiceContract;
use JOOservices\LaravelChatGateway\Models\ChatAttachment;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class WebhookServiceTest extends TestCase
{
    public function test_it_processes_inbound_telegram_webhook_and_persists_operational_records(): void
    {
        $channel = $this->makeTelegramChannel();
        $request = Request::create('/chat-gateway/webhooks/telegram/'.$channel->channel_key, 'POST', [], [], [], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'telegram-secret',
        ], json_encode($this->loadJsonFixture('telegram/inbound_media.json'), JSON_THROW_ON_ERROR));
        $request->replace($this->loadJsonFixture('telegram/inbound_media.json'));

        $event = $this->app->make(WebhookServiceContract::class)->process($request, 'telegram', $channel->channel_key);

        $this->assertSame('processed', $event->status);
        $this->assertDatabaseCount('chat_webhook_events', 1);
        $this->assertDatabaseCount('chat_contacts', 1);
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertDatabaseCount('chat_attachments', 1);
        $this->assertInstanceOf(ChatWebhookEvent::class, $event);
        $this->assertInstanceOf(ChatContact::class, ChatContact::query()->first());
        $this->assertInstanceOf(ChatConversation::class, ChatConversation::query()->first());
        $this->assertInstanceOf(ChatMessage::class, ChatMessage::query()->first());
        $this->assertInstanceOf(ChatAttachment::class, ChatAttachment::query()->first());
    }

    public function test_it_deduplicates_repeated_webhooks(): void
    {
        $channel = $this->makeTelegramChannel();
        $payload = $this->loadJsonFixture('telegram/inbound_text.json');

        $first = Request::create('/chat-gateway/webhooks/telegram/'.$channel->channel_key, 'POST', [], [], [], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'telegram-secret',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
        $first->replace($payload);

        $second = Request::create('/chat-gateway/webhooks/telegram/'.$channel->channel_key, 'POST', [], [], [], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'telegram-secret',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
        $second->replace($payload);

        $service = $this->app->make(WebhookServiceContract::class);
        $service->process($first, 'telegram', $channel->channel_key);
        $event = $service->process($second, 'telegram', $channel->channel_key);

        $this->assertSame('deduplicated', $event->status);
    }

    public function test_it_uses_channel_aware_dedupe_for_webhooks(): void
    {
        $firstChannel = $this->makeTelegramChannel('telegram-one');
        $secondChannel = $this->makeTelegramChannel('telegram-two', false);
        $payloadOne = $this->loadJsonFixture('telegram/inbound_text.json');
        $payloadTwo = $this->loadJsonFixture('telegram/inbound_text.json');
        $payloadTwo['message']['message_id'] = 12;

        $first = Request::create('/chat-gateway/webhooks/telegram/'.$firstChannel->channel_key, 'POST', [], [], [], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'telegram-secret',
        ], json_encode($payloadOne, JSON_THROW_ON_ERROR));
        $first->replace($payloadOne);

        $second = Request::create('/chat-gateway/webhooks/telegram/'.$secondChannel->channel_key, 'POST', [], [], [], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'telegram-secret',
        ], json_encode($payloadTwo, JSON_THROW_ON_ERROR));
        $second->replace($payloadTwo);

        $service = $this->app->make(WebhookServiceContract::class);
        $firstEvent = $service->process($first, 'telegram', $firstChannel->channel_key);
        $secondEvent = $service->process($second, 'telegram', $secondChannel->channel_key);

        $this->assertSame('processed', $firstEvent->status);
        $this->assertSame('processed', $secondEvent->status);
        $this->assertDatabaseHas('chat_webhook_events', ['channel_id' => $firstChannel->id, 'external_event_id' => '1001', 'status' => 'processed']);
        $this->assertDatabaseHas('chat_webhook_events', ['channel_id' => $secondChannel->id, 'external_event_id' => '1001', 'status' => 'processed']);
    }

    private function makeTelegramChannel(string $channelKey = 'telegram-default', bool $isDefault = true): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => $channelKey,
            'name' => 'Telegram',
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
