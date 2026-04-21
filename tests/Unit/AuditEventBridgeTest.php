<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit;

use JOOservices\LaravelChatGateway\Events\ConversationCreated;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Services\AuditEventBridge;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use JooServices\LaravelEvents\EventService;
use Mockery;

final class AuditEventBridgeTest extends TestCase
{
    public function test_it_bridges_conversation_created_to_audit_and_sourcing_service(): void
    {
        config()->set('chat-gateway.events.audit_enabled', true);
        config()->set('chat-gateway.events.sourcing_enabled', true);

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

        $service = Mockery::mock(EventService::class);
        $service->shouldReceive('logChange')->once();
        $service->shouldReceive('storeEvent')->once();

        $bridge = new AuditEventBridge($service);
        $bridge->handle(new ConversationCreated($conversation));

        $this->assertTrue(true);
    }
}
