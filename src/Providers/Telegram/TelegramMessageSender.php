<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Telegram;

use JOOservices\LaravelChatGateway\Contracts\Providers\OutboundMessageSenderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\DTOs\AttachmentDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class TelegramMessageSender implements OutboundMessageSenderContract
{
    public function __construct(
        private readonly ProviderHttpClientFactoryContract $clientFactory,
        private readonly TelegramMapper $mapper,
    ) {}

    public function send(ChatChannel $channel, OutboundMessageDto $message): OutboundMessageResultDto
    {
        $token = (string) ($channel->credentials['bot_token'] ?? '');
        $chatId = $message->externalChatId ?? (string) ($message->meta['external_chat_id'] ?? '');

        if ($token === '' || $chatId === '') {
            return new OutboundMessageResultDto(false, 'failed', errorMessage: 'Telegram credentials or chat id missing.');
        }

        $endpoint = '/bot'.$token.'/sendMessage';
        $payload = ['chat_id' => $chatId, 'text' => $message->content ?? ''];

        if ($message->type === 'image') {
            $endpoint = '/bot'.$token.'/sendPhoto';
            /** @var AttachmentDto|null $attachment */
            $attachment = $message->attachments[0] ?? null;
            $payload = [
                'chat_id' => $chatId,
                'photo' => $attachment !== null ? ($attachment->url ?? $attachment->externalFileId) : null,
                'caption' => $message->content,
            ];
        }

        if ($message->type === 'file') {
            $endpoint = '/bot'.$token.'/sendDocument';
            /** @var AttachmentDto|null $attachment */
            $attachment = $message->attachments[0] ?? null;
            $payload = [
                'chat_id' => $chatId,
                'document' => $attachment !== null ? ($attachment->url ?? $attachment->externalFileId) : null,
                'caption' => $message->content,
            ];
        }

        $response = $this->clientFactory->make($channel)->post($endpoint, ['json' => $payload]);

        return $this->mapper->mapSendResult($response->json());
    }
}
