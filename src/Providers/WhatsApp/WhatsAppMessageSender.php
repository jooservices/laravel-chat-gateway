<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\WhatsApp;

use JOOservices\LaravelChatGateway\Contracts\Providers\OutboundMessageSenderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class WhatsAppMessageSender implements OutboundMessageSenderContract
{
    public function __construct(
        private readonly ProviderHttpClientFactoryContract $clientFactory,
        private readonly WhatsAppMapper $mapper,
    ) {}

    public function send(ChatChannel $channel, OutboundMessageDto $message): OutboundMessageResultDto
    {
        $accessToken = (string) ($channel->credentials['access_token'] ?? '');
        $phoneNumberId = (string) ($channel->credentials['phone_number_id'] ?? '');
        $recipient = $message->externalChatId ?? (string) ($message->meta['external_chat_id'] ?? '');

        if ($accessToken === '' || $phoneNumberId === '' || $recipient === '') {
            return new OutboundMessageResultDto(false, 'failed', errorMessage: 'WhatsApp credentials or recipient missing.');
        }

        $apiVersion = (string) config('chat-gateway.providers.whatsapp.api_version', 'v21.0');
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
        ];

        if ($message->type === 'image') {
            $attachment = $message->attachments[0] ?? null;
            $payload['type'] = 'image';
            $payload['image'] = ['link' => $attachment?->url, 'caption' => $message->content];
        } elseif ($message->type === 'file') {
            $attachment = $message->attachments[0] ?? null;
            $payload['type'] = 'document';
            $payload['document'] = ['link' => $attachment?->url, 'filename' => $attachment?->fileName, 'caption' => $message->content];
        } elseif ($message->type === 'button' && isset($message->meta['interactive']) && is_array($message->meta['interactive'])) {
            $payload['type'] = 'interactive';
            $payload['interactive'] = $message->meta['interactive'];
        } else {
            $payload['type'] = 'text';
            $payload['text'] = ['body' => $message->content ?? ''];
        }

        $response = $this->clientFactory->make($channel, ['Authorization' => 'Bearer '.$accessToken])->post('/'.$apiVersion.'/'.$phoneNumberId.'/messages', [
            'json' => $payload,
        ]);

        return $this->mapper->mapSendResult($response->json());
    }
}
