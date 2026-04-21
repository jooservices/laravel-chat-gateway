<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Services;

use JOOservices\LaravelChatGateway\Contracts\Services\InboundIngestionServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;
use JOOservices\LaravelChatGateway\DTOs\ContactDto;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Tests\TestCase;
use Mockery;
use RuntimeException;

final class InboundIngestionServiceTest extends TestCase
{
    public function test_it_rolls_back_all_writes_when_ingestion_fails(): void
    {
        $channel = ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => 'telegram-default',
            'name' => 'Telegram',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['bot_token' => 'bot-token'],
            'settings' => ['inbound_mode' => 'callback', 'webhook' => ['enabled' => true]],
            'webhook_secret' => 'telegram-secret',
        ]);

        $messageService = Mockery::mock(MessageServiceContract::class);
        $messageService->shouldReceive('createInbound')->once()->andThrow(new RuntimeException('ingest failed'));
        $messageService->shouldReceive('updateStatus')->never();
        $this->app->instance(MessageServiceContract::class, $messageService);
        $this->app->forgetInstance(InboundIngestionServiceContract::class);

        $event = new InboundWebhookDto(
            externalEventId: 'telegram-event-1',
            eventType: 'message',
            messageType: 'text',
            interactionType: null,
            isMessageEvent: true,
            isStatusEvent: false,
            isInteractionEvent: false,
            contact: new ContactDto(externalContactId: 'telegram-user-1', displayName: 'Telegram User'),
            conversation: new ConversationContextDto(externalChatId: 'telegram-chat-1'),
            externalMessageId: 'message-1',
            content: 'Hello',
            normalizedPayload: ['ok' => true],
        );

        try {
            $this->app->make(InboundIngestionServiceContract::class)->ingest('telegram', $channel, $event, ['raw' => true]);
            $this->fail('Expected ingestion to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('ingest failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('chat_contacts', 0);
        $this->assertDatabaseCount('chat_conversations', 0);
        $this->assertDatabaseCount('chat_messages', 0);
    }
}