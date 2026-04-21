<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Telegram;

use Illuminate\Support\Arr;
use JOOservices\LaravelChatGateway\DTOs\AttachmentDto;
use JOOservices\LaravelChatGateway\DTOs\ContactDto;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;

final class TelegramMapper
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapInbound(array $payload): InboundWebhookDto
    {
        $message = Arr::get($payload, 'message');
        $callback = Arr::get($payload, 'callback_query');
        $membership = Arr::get($payload, 'my_chat_member') ?? Arr::get($payload, 'chat_member');

        if (is_array($callback)) {
            $from = (array) Arr::get($callback, 'from', []);
            $sourceMessage = (array) Arr::get($callback, 'message', []);

            return new InboundWebhookDto(
                externalEventId: (string) Arr::get($payload, 'update_id', uniqid('telegram_', true)),
                eventType: 'callback_action',
                messageType: 'button',
                interactionType: 'button',
                isMessageEvent: false,
                isStatusEvent: false,
                isInteractionEvent: true,
                contact: $this->mapContact($from),
                conversation: new ConversationContextDto((string) Arr::get($sourceMessage, 'chat.id', 'telegram-system')),
                externalMessageId: (string) Arr::get($sourceMessage, 'message_id'),
                content: (string) Arr::get($callback, 'data', ''),
                normalizedPayload: $callback,
                providerMetadata: ['callback_id' => Arr::get($callback, 'id')],
                occurredAt: Arr::has($sourceMessage, 'date') ? now()->setTimestamp((int) Arr::get($sourceMessage, 'date'))->toIso8601String() : null,
            );
        }

        if (is_array($message)) {
            $attachments = [];
            $messageType = 'text';
            $content = (string) Arr::get($message, 'text', Arr::get($message, 'caption', ''));

            if (is_array(Arr::get($message, 'photo'))) {
                $photo = collect((array) Arr::get($message, 'photo'))->last();
                $messageType = 'image';
                $attachments[] = new AttachmentDto(
                    type: 'image',
                    externalFileId: (string) Arr::get($photo, 'file_id'),
                    fileSize: Arr::has($photo, 'file_size') ? (int) Arr::get($photo, 'file_size') : null,
                    meta: ['width' => Arr::get($photo, 'width'), 'height' => Arr::get($photo, 'height')],
                );
            }

            if (is_array(Arr::get($message, 'document'))) {
                $document = (array) Arr::get($message, 'document');
                $messageType = 'file';
                $attachments[] = new AttachmentDto(
                    type: 'file',
                    externalFileId: (string) Arr::get($document, 'file_id'),
                    mimeType: Arr::get($document, 'mime_type'),
                    fileName: Arr::get($document, 'file_name'),
                    fileSize: Arr::has($document, 'file_size') ? (int) Arr::get($document, 'file_size') : null,
                );
            }

            return new InboundWebhookDto(
                externalEventId: (string) Arr::get($payload, 'update_id', uniqid('telegram_', true)),
                eventType: 'message',
                messageType: $messageType,
                interactionType: null,
                isMessageEvent: true,
                isStatusEvent: false,
                isInteractionEvent: false,
                contact: $this->mapContact((array) Arr::get($message, 'from', [])),
                conversation: new ConversationContextDto((string) Arr::get($message, 'chat.id', 'telegram-system')),
                externalMessageId: (string) Arr::get($message, 'message_id'),
                content: $content,
                attachments: $attachments,
                normalizedPayload: $message,
                providerMetadata: ['chat_type' => Arr::get($message, 'chat.type')],
                occurredAt: Arr::has($message, 'date') ? now()->setTimestamp((int) Arr::get($message, 'date'))->toIso8601String() : null,
            );
        }

        if (is_array($membership)) {
            $from = (array) Arr::get($membership, 'from', []);

            return new InboundWebhookDto(
                externalEventId: (string) Arr::get($payload, 'update_id', uniqid('telegram_', true)),
                eventType: 'membership',
                messageType: 'system',
                interactionType: null,
                isMessageEvent: false,
                isStatusEvent: false,
                isInteractionEvent: false,
                contact: $this->mapContact($from),
                conversation: new ConversationContextDto((string) Arr::get($membership, 'chat.id', 'telegram-system')),
                content: (string) Arr::get($membership, 'new_chat_member.status', 'membership_change'),
                normalizedPayload: $membership,
                providerMetadata: ['conversation_status' => Arr::get($membership, 'new_chat_member.status')],
            );
        }

        return new InboundWebhookDto(
            externalEventId: (string) Arr::get($payload, 'update_id', uniqid('telegram_', true)),
            eventType: 'system',
            messageType: 'system',
            interactionType: null,
            isMessageEvent: false,
            isStatusEvent: false,
            isInteractionEvent: false,
            contact: new ContactDto('telegram-system'),
            conversation: new ConversationContextDto('telegram-system'),
            normalizedPayload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapSendResult(array $payload): OutboundMessageResultDto
    {
        $ok = (bool) Arr::get($payload, 'ok', false);

        return new OutboundMessageResultDto(
            successful: $ok,
            status: $ok ? 'sent' : 'failed',
            externalMessageId: $ok ? (string) Arr::get($payload, 'result.message_id') : null,
            providerStatus: $ok ? 'ok' : (string) Arr::get($payload, 'description', 'error'),
            errorMessage: $ok ? null : (string) Arr::get($payload, 'description', 'Telegram send failed.'),
            responsePayload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $from
     */
    private function mapContact(array $from): ContactDto
    {
        $displayName = trim((string) Arr::get($from, 'first_name', '').' '.(string) Arr::get($from, 'last_name', ''));

        return new ContactDto(
            externalContactId: (string) Arr::get($from, 'id', 'telegram-anonymous'),
            externalUsername: Arr::get($from, 'username'),
            displayName: $displayName !== '' ? $displayName : null,
            meta: ['language_code' => Arr::get($from, 'language_code')],
        );
    }
}
