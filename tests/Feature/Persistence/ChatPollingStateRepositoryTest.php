<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Persistence;

use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatPollingStateRepositoryContract;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ChatPollingStateRepositoryTest extends TestCase
{
    public function test_it_creates_updates_and_resets_polling_state(): void
    {
        $channel = ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => 'telegram-default',
            'name' => 'Telegram',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['bot_token' => 'bot-token'],
            'settings' => ['inbound_mode' => 'poll', 'polling' => ['enabled' => true]],
            'webhook_secret' => 'telegram-secret',
        ]);

        $repository = $this->app->make(ChatPollingStateRepositoryContract::class);
        $state = $repository->findOrCreateForChannel('telegram', $channel);

        $this->assertSame(0, $state->offset);

        $updated = $repository->updateOffset($state, 1002, ['last_update_id' => '1001']);

        $this->assertSame(1002, $updated->offset);
        $this->assertSame('1001', $updated->meta['last_update_id']);

        $reset = $repository->resetOffset($updated);

        $this->assertSame(0, $reset->offset);
    }
}
