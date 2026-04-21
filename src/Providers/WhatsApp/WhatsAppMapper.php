<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\WhatsApp;

use Illuminate\Support\Arr;
use JOOservices\LaravelChatGateway\DTOs\AttachmentDto;
use JOOservices\LaravelChatGateway\DTOs\ContactDto;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;

final class WhatsAppMapper
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapInbound(array $payload): InboundWebhookDto
    {
        $change = (array) Arr::first((array) Arr::get($payload, 'entry.0.changes', []));
        $value = (array) Arr::get($change, 'value', []);
        $message = (array) Arr::first((array) Arr::get($value, 'messages', []), static fn (): bool => true, []);
        $status = (array) Arr::first((array) Arr::get($value, 'statuses', []), static fn (): bool => true, []);
        $contact = (array) Arr::first((array) Arr::get($value, 'contacts', []), static fn (): bool => true, []);

        if ($message !== []) {
            $type = (string) Arr::get($message, 'type', 'text');
            $attachments = [];
            $content = (string) Arr::get($message, 'text.body', '');
            $eventType = 'message';
            $interactionType = null;
            $isInteraction = false;

            if ($type === 'image') {
                $attachments[] = new AttachmentDto(type: 'image', externalFileId: (string) Arr::get($message, 'image.id'), mimeType: Arr::get($message, 'image.mime_type'));
                $content = (string) Arr::get($message, 'image.caption', '');
            }

            if ($type === 'document') {
                $attachments[] = new AttachmentDto(
                    type: 'file',
                    externalFileId: (string) Arr::get($message, 'document.id'),
                    mimeType: Arr::get($message, 'document.mime_type'),
                    fileName: Arr::get($message, 'document.filename')
                );
                $type = 'file';
            }

            if ($type === 'button' || $type === 'interactive') {
                $eventType = 'callback_action';
                $interactionType = $type === 'button' ? 'button' : (string) (Arr::get($message, 'interactive.type') ?? 'interactive');
                $isInteraction = true;
                $content = (string) (Arr::get($message, 'button.text') ?? Arr::get($message, 'interactive.button_reply.title') ?? Arr::get($message, 'interactive.list_reply.title', ''));
                $type = 'button';
            }

            return new InboundWebhookDto(
                externalEventId: (string) Arr::get($message, 'id', uniqid('wa_', true)),
                eventType: $eventType,
                messageType: $type,
                interactionType: $interactionType,
                isMessageEvent: ! $isInteraction,
                isStatusEvent: false,
                isInteractionEvent: $isInteraction,
                contact: new ContactDto(
                    externalContactId: (string) Arr::get($contact, 'wa_id', Arr::get($message, 'from', 'wa-anonymous')),
                    displayName: Arr::get($contact, 'profile.name'),
                    phoneNumber: Arr::get($contact, 'wa_id'),
                ),
                conversation: new ConversationContextDto((string) Arr::get($message, 'from', 'wa-system')),
                externalMessageId: (string) Arr::get($message, 'id'),
                content: $content,
                attachments: $attachments,
                normalizedPayload: $message,
                providerMetadata: ['phone_number_id' => Arr::get($value, 'metadata.phone_number_id')],
                occurredAt: Arr::get($message, 'timestamp') !== null ? now()->setTimestamp((int) Arr::get($message, 'timestamp'))->toIso8601String() : null,
            );
        }

        if ($status !== []) {
            $statusValue = (string) Arr::get($status, 'status', 'sent');
            $eventType = $statusValue === 'read' ? 'read_status' : 'delivery_status';

            return new InboundWebhookDto(
                externalEventId: (string) Arr::get($status, 'id', uniqid('wa_status_', true)),
                eventType: $eventType,
                messageType: null,
                interactionType: null,
                isMessageEvent: false,
                isStatusEvent: true,
                isInteractionEvent: false,
                contact: new ContactDto(
                    externalContactId: (string) Arr::get($status, 'recipient_id', 'wa-anonymous'),
                    phoneNumber: Arr::get($status, 'recipient_id'),
                ),
                conversation: new ConversationContextDto((string) Arr::get($status, 'recipient_id', 'wa-system')),
                externalMessageId: (string) Arr::get($status, 'id', ''),
                providerStatus: $statusValue,
                normalizedPayload: $status,
            );
        }

        return new InboundWebhookDto(
            externalEventId: uniqid('wa_system_', true),
            eventType: 'system',
            messageType: 'system',
            interactionType: null,
            isMessageEvent: false,
            isStatusEvent: false,
            isInteractionEvent: false,
            contact: new ContactDto('wa-system'),
            conversation: new ConversationContextDto('wa-system'),
            normalizedPayload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapSendResult(array $payload): OutboundMessageResultDto
    {
        $message = (array) Arr::first((array) Arr::get($payload, 'messages', []), static fn (): bool => true, []);
        $successful = $message !== [];

        return new OutboundMessageResultDto(
            successful: $successful,
            status: $successful ? 'sent' : 'failed',
            externalMessageId: $successful ? (string) Arr::get($message, 'id') : null,
            providerStatus: $successful ? 'accepted' : (string) Arr::get($payload, 'error.message', 'error'),
            errorMessage: $successful ? null : (string) Arr::get($payload, 'error.message', 'WhatsApp send failed.'),
            responsePayload: $payload,
        );
    }
}
