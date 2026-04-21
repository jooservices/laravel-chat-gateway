<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use JOOservices\LaravelChatGateway\Contracts\Providers\ChatProviderContract;
use JOOservices\LaravelChatGateway\Exceptions\UnsupportedProviderException;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramProvider;
use JOOservices\LaravelChatGateway\Providers\Viber\ViberProvider;
use JOOservices\LaravelChatGateway\Providers\WhatsApp\WhatsAppProvider;

final class ChatGatewayManager
{
    /**
     * @var array<string, ChatProviderContract>
     */
    private array $providers;

    public function __construct(
        TelegramProvider $telegramProvider,
        ViberProvider $viberProvider,
        WhatsAppProvider $whatsAppProvider,
    ) {
        $this->providers = [
            $telegramProvider->name() => $telegramProvider,
            $viberProvider->name() => $viberProvider,
            $whatsAppProvider->name() => $whatsAppProvider,
        ];
    }

    public function defaultProvider(): ChatProviderContract
    {
        return $this->provider((string) config('chat-gateway.default_provider', 'telegram'));
    }

    public function providerForChannel(ChatChannel $channel): ChatProviderContract
    {
        return $this->provider($channel->provider);
    }

    public function provider(string $provider): ChatProviderContract
    {
        $providerInstance = $this->providers[$provider] ?? null;

        if ($providerInstance === null) {
            throw new UnsupportedProviderException('Unsupported provider ['.$provider.'].');
        }

        return $providerInstance;
    }
}
