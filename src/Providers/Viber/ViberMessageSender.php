<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Viber;

use JOOservices\LaravelChatGateway\Contracts\Providers\OutboundMessageSenderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class ViberMessageSender implements OutboundMessageSenderContract
{
    public function __construct(
        private readonly ProviderHttpClientFactoryContract $clientFactory,
        private readonly ViberMapper $mapper,
    ) {}

    public function send(ChatChannel $channel, OutboundMessageDto $message): OutboundMessageResultDto
    {
        $authToken = (string) ($channel->credentials['auth_token'] ?? $channel->credentials['access_token'] ?? '');
        $receiver = $message->externalChatId ?? (string) ($message->meta['external_chat_id'] ?? '');

        if ($authToken === '' || $receiver === '') {
            return new OutboundMessageResultDto(false, 'failed', errorMessage: 'Viber credentials or receiver missing.');
        }

        $messagePayload = ['type' => 'text', 'text' => $message->content ?? ''];

        if ($message->type === 'image') {
            $attachment = $message->attachments[0] ?? null;
            $messagePayload = ['type' => 'picture', 'media' => $attachment?->url, 'text' => $message->content];
        }

        if ($message->type === 'file') {
            $attachment = $message->attachments[0] ?? null;
            $messagePayload = [
                'type' => 'file',
                'media' => $attachment?->url,
                'file_name' => $attachment?->fileName,
                'size' => $attachment?->fileSize,
            ];
        }

        $response = $this->clientFactory->make($channel, ['X-Viber-Auth-Token' => $authToken])->post('/pa/send_message', [
            'json' => [
                'receiver' => $receiver,
                'min_api_version' => 7,
                'sender' => ['name' => $channel->name],
                'message' => $messagePayload,
            ],
        ]);

        return $this->mapper->mapSendResult($response->json());
    }
}
