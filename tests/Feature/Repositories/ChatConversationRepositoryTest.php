<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Repositories;

use Carbon\CarbonImmutable;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatConversationRepositoryContract;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ChatConversationRepositoryTest extends TestCase
{
    public function test_it_does_not_overwrite_started_at_when_updating_existing_conversation(): void
    {
        $repository = $this->app->make(ChatConversationRepositoryContract::class);
        [$channel, $contact] = $this->makeChannelAndContact();

        $conversation = $repository->createOrUpdate($channel, $contact, new ConversationContextDto(externalChatId: 'chat-1'));
        $originalStartedAt = CarbonImmutable::parse('2026-01-01 00:00:00');
        $conversation->forceFill(['started_at' => $originalStartedAt])->save();

        $updated = $repository->createOrUpdate($channel, $contact, new ConversationContextDto(externalChatId: 'chat-1'));

        $this->assertSame($originalStartedAt->toDateTimeString(), $updated->started_at?->toDateTimeString());
    }

    public function test_it_persists_chat_origin_fields_and_preserves_existing_values_when_new_values_are_null(): void
    {
        $repository = $this->app->make(ChatConversationRepositoryContract::class);
        [$channel, $contact] = $this->makeChannelAndContact();

        $created = $repository->createOrUpdate($channel, $contact, new ConversationContextDto(
            externalChatId: 'chat-1',
            chatType: 'group',
            chatTitle: 'Support Room',
            chatUsername: 'support_room',
        ));

        $updated = $repository->createOrUpdate($channel, $contact, new ConversationContextDto(
            externalChatId: 'chat-1',
            chatType: null,
            chatTitle: null,
            chatUsername: null,
        ));

        $this->assertSame('group', $created->chat_type);
        $this->assertSame('group', $updated->chat_type);
        $this->assertSame('Support Room', $updated->chat_title);
        $this->assertSame('support_room', $updated->chat_username);
        $this->assertDatabaseHas('chat_conversations', [
            'id' => $updated->id,
            'chat_type' => 'group',
            'chat_title' => 'Support Room',
            'chat_username' => 'support_room',
        ]);
    }

    /**
     * @return array{0: ChatChannel, 1: ChatContact}
     */
    private function makeChannelAndContact(): array
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
            'external_contact_id' => 'telegram-user-1',
        ]);

        return [$channel, $contact];
    }
}