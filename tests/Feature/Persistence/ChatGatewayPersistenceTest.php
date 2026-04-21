<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Persistence;

use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelChatGateway\Models\ChatAttachment;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelChatGateway\Models\ChatMessageStatusLog;
use JOOservices\LaravelChatGateway\Models\ChatPollingState;
use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ChatGatewayPersistenceTest extends TestCase
{
    public function test_package_migrations_create_operational_tables(): void
    {
        $this->assertTrue(Schema::hasTable('chat_channels'));
        $this->assertTrue(Schema::hasTable('chat_contacts'));
        $this->assertTrue(Schema::hasTable('chat_conversations'));
        $this->assertTrue(Schema::hasTable('chat_messages'));
        $this->assertTrue(Schema::hasTable('chat_attachments'));
        $this->assertTrue(Schema::hasTable('chat_webhook_events'));
        $this->assertTrue(Schema::hasTable('chat_message_status_logs'));
        $this->assertTrue(Schema::hasTable('chat_polling_states'));
    }

    public function test_model_relationships_are_wired(): void
    {
        $channel = ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => 'telegram-default',
            'name' => 'Telegram',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['bot_token' => 'bot-token'],
            'settings' => [],
            'webhook_secret' => 'telegram-secret',
        ]);
        $contact = ChatContact::query()->create([
            'provider' => 'telegram',
            'channel_id' => $channel->id,
            'external_contact_id' => 'tg-user-1',
        ]);
        $conversation = ChatConversation::query()->create([
            'channel_id' => $channel->id,
            'contact_id' => $contact->id,
            'external_chat_id' => 'tg-chat-1',
            'status' => 'open',
        ]);
        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'provider' => 'telegram',
            'direction' => 'inbound',
            'type' => 'text',
            'status' => 'sent',
        ]);
        $attachment = ChatAttachment::query()->create([
            'message_id' => $message->id,
            'type' => 'image',
        ]);
        $webhook = ChatWebhookEvent::query()->create([
            'channel_id' => $channel->id,
            'provider' => 'telegram',
            'transport' => 'callback',
            'payload' => ['ok' => true],
            'status' => 'received',
        ]);
        $pollingState = ChatPollingState::query()->create([
            'provider' => 'telegram',
            'channel_id' => $channel->id,
            'offset' => 1002,
        ]);
        $log = ChatMessageStatusLog::query()->create([
            'message_id' => $message->id,
            'new_status' => 'sent',
        ]);

        $this->assertCount(1, $channel->conversations);
        $this->assertCount(1, $channel->webhookEvents);
        $this->assertCount(1, $channel->pollingStates);
        $this->assertCount(1, $contact->conversations);
        $this->assertSame($channel->id, $conversation->channel->id);
        $this->assertSame($contact->id, $conversation->contact->id);
        $this->assertCount(1, $conversation->messages);
        $this->assertSame($conversation->id, $message->conversation->id);
        $this->assertCount(1, $message->attachments);
        $this->assertCount(1, $message->statusLogs);
        $this->assertSame($message->id, $attachment->message->id);
        $this->assertSame($message->id, $log->message->id);
        $this->assertSame($channel->id, $webhook->channel->id);
        $this->assertSame($channel->id, $pollingState->channel_id);
    }
}
