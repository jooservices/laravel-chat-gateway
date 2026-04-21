<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Services;

use Illuminate\Validation\ValidationException;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderChannelServiceContract;
use JOOservices\LaravelChatGateway\DTOs\ProviderChannelUpsertDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ProviderChannelServiceTest extends TestCase
{
    public function test_it_validates_credentials_before_persisting(): void
    {
        $service = $this->app->make(ProviderChannelServiceContract::class);

        $this->expectException(ValidationException::class);

        $service->registerChannel('telegram', new ProviderChannelUpsertDto(
            channelKey: 'telegram-invalid',
            name: 'Telegram Invalid',
            credentials: [],
            webhookSecret: 'secret',
        ));
    }

    public function test_it_validates_settings_before_persisting(): void
    {
        $service = $this->app->make(ProviderChannelServiceContract::class);

        $this->expectException(ValidationException::class);

        $service->registerChannel('telegram', new ProviderChannelUpsertDto(
            channelKey: 'telegram-invalid-settings',
            name: 'Telegram Invalid Settings',
            credentials: ['bot_token' => 'bot-token'],
            webhookSecret: 'secret',
            settings: ['webhook' => ['enabled' => 'nope']],
        ));
    }

    public function test_it_persists_channels_through_repository_and_manages_status_and_default(): void
    {
        $service = $this->app->make(ProviderChannelServiceContract::class);

        $first = $service->registerChannel('telegram', new ProviderChannelUpsertDto(
            channelKey: 'telegram-1',
            name: 'Telegram One',
            credentials: ['bot_token' => 'bot-token-1'],
            webhookSecret: 'secret-1',
            settings: [
                'inbound_mode' => 'callback',
                'webhook' => ['enabled' => true],
            ],
            isDefault: true,
        ));

        $second = $service->registerChannel('telegram', new ProviderChannelUpsertDto(
            channelKey: 'telegram-2',
            name: 'Telegram Two',
            credentials: ['bot_token' => 'bot-token-2'],
            webhookSecret: 'secret-2',
            settings: [
                'inbound_mode' => 'callback',
                'webhook' => ['enabled' => true],
            ],
        ));

        $second = $service->updateChannel($second, new ProviderChannelUpsertDto(
            channelKey: 'telegram-2',
            name: 'Telegram Two Updated',
            credentials: ['bot_token' => 'bot-token-2b'],
            webhookSecret: 'secret-2',
            settings: [
                'inbound_mode' => 'poll',
                'polling' => ['enabled' => true],
            ],
        ));

        $second = $service->deactivate($second);
        $second = $service->activate($second);
        $second = $service->markDefault($second);

        $this->assertInstanceOf(ChatChannel::class, $first);
        $this->assertSame('Telegram Two Updated', $second->name);
        $this->assertSame('active', $second->status);
        $this->assertTrue($second->is_default);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertDatabaseHas('chat_channels', [
            'provider' => 'telegram',
            'channel_key' => 'telegram-2',
            'name' => 'Telegram Two Updated',
            'status' => 'active',
            'is_default' => true,
        ]);
    }
}