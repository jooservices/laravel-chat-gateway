<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Feature\Http;

use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class WebhookControllerTest extends TestCase
{
    public function test_it_accepts_webhook_through_real_route(): void
    {
        $channel = $this->makeTelegramChannel();

        $response = $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'])
            ->postJson('/chat-gateway/webhooks/telegram/'.$channel->channel_key, $this->loadJsonFixture('telegram/inbound_text.json'));

        $response->assertAccepted();
        $response->assertJsonPath('data.status', 'processed');
    }

    public function test_it_verifies_whatsapp_challenge_through_real_route(): void
    {
        ChatChannel::query()->create([
            'provider' => 'whatsapp',
            'channel_key' => 'wa-channel-1',
            'name' => 'WhatsApp',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['access_token' => 'wa-token', 'phone_number_id' => '1234567890', 'app_secret' => 'wa-app-secret'],
            'settings' => [
                'inbound_mode' => 'callback',
                'webhook' => ['enabled' => true],
            ],
            'webhook_secret' => 'wa-secret',
        ]);

        $response = $this->getJson('/chat-gateway/webhooks/whatsapp/wa-channel-1/verify?hub_verify_token=wa-secret&hub_challenge=12345');

        $response->assertOk();
        $response->assertJsonPath('data.challenge', '12345');
    }

    private function makeTelegramChannel(): ChatChannel
    {
        return ChatChannel::query()->create([
            'provider' => 'telegram',
            'channel_key' => 'telegram-default',
            'name' => 'Telegram',
            'status' => 'active',
            'is_default' => true,
            'credentials' => ['bot_token' => 'bot-token'],
            'settings' => [
                'inbound_mode' => 'callback',
                'webhook' => ['enabled' => true],
            ],
            'webhook_secret' => 'telegram-secret',
        ]);
    }
}
