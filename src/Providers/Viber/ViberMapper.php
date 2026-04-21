<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Viber;

use Illuminate\Support\Arr;
use JOOservices\LaravelChatGateway\DTOs\AttachmentDto;
use JOOservices\LaravelChatGateway\DTOs\ContactDto;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;

final class ViberMapper
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapInbound(array $payload): InboundWebhookDto
    {
        $event = (string) Arr::get($payload, 'event', 'system');
        $sender = (array) Arr::get($payload, 'sender', []);
        $message = (array) Arr::get($payload, 'message', []);
        $externalChatId = (string) ($payload['sender']['id'] ?? $payload['user_id'] ?? 'viber-system');
        $externalEventId = (string) Arr::get($payload, 'message_token', uniqid('viber_', true));

        if ($event === 'message') {
            $messageType = (string) Arr::get($message, 'type', 'text');
            $attachments = [];
            $interactionType = Arr::has($message, 'tracking_data') ? 'button' : null;
            $eventType = $interactionType !== null ? 'callback_action' : 'message';
            $isInteraction = $interactionType !== null;

            if ($messageType === 'picture') {
                $attachments[] = new AttachmentDto(type: 'image', url: Arr::get($message, 'media'), fileSize: Arr::has($message, 'size') ? (int) Arr::get($message, 'size') : null);
                $messageType = 'image';
            }

            if ($messageType === 'file') {
                $attachments[] = new AttachmentDto(
                    type: 'file',
                    url: Arr::get($message, 'media'),
                    fileName: Arr::get($message, 'file_name'),
                    fileSize: Arr::has($message, 'size') ? (int) Arr::get($message, 'size') : null,
                );
            }

            return new InboundWebhookDto(
                externalEventId: $externalEventId,
                eventType: $eventType,
                messageType: $messageType,
                interactionType: $interactionType,
                isMessageEvent: ! $isInteraction,
                isStatusEvent: false,
                isInteractionEvent: $isInteraction,
                contact: $this->mapContact($sender),
                conversation: new ConversationContextDto($externalChatId),
                externalMessageId: $externalEventId,
                content: (string) Arr::get($message, 'text', Arr::get($message, 'tracking_data', '')),
                attachments: $attachments,
                normalizedPayload: $payload,
            );
        }

        if ($event === 'delivered') {
            return new InboundWebhookDto(
                externalEventId: $externalEventId,
                eventType: 'delivery_status',
                messageType: null,
                interactionType: null,
                isMessageEvent: false,
                isStatusEvent: true,
                isInteractionEvent: false,
                contact: $this->mapContact($sender),
                conversation: new ConversationContextDto($externalChatId),
                externalMessageId: (string) Arr::get($payload, 'message_token', ''),
                providerStatus: 'delivered',
                normalizedPayload: $payload,
            );
        }

        if ($event === 'seen') {
            return new InboundWebhookDto(
                externalEventId: $externalEventId,
                eventType: 'read_status',
                messageType: null,
                interactionType: null,
                isMessageEvent: false,
                isStatusEvent: true,
                isInteractionEvent: false,
                contact: $this->mapContact($sender),
                conversation: new ConversationContextDto($externalChatId),
                externalMessageId: (string) Arr::get($payload, 'message_token', ''),
                providerStatus: 'read',
                normalizedPayload: $payload,
            );
        }

        return new InboundWebhookDto(
            externalEventId: $externalEventId,
            eventType: in_array($event, ['subscribed', 'unsubscribed', 'conversation_started'], true) ? 'membership' : 'system',
            messageType: 'system',
            interactionType: null,
            isMessageEvent: false,
            isStatusEvent: false,
            isInteractionEvent: false,
            contact: $this->mapContact($sender),
            conversation: new ConversationContextDto($externalChatId),
            content: $event,
            normalizedPayload: $payload,
            providerMetadata: ['conversation_status' => $event === 'unsubscribed' ? 'closed' : 'open'],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapSendResult(array $payload): OutboundMessageResultDto
    {
        $successful = (int) Arr::get($payload, 'status', 1) === 0;

        return new OutboundMessageResultDto(
            successful: $successful,
            status: $successful ? 'sent' : 'failed',
            externalMessageId: $successful ? (string) Arr::get($payload, 'message_token') : null,
            providerStatus: (string) Arr::get($payload, 'status_message', $successful ? 'ok' : 'error'),
            errorMessage: $successful ? null : (string) Arr::get($payload, 'status_message', 'Viber send failed.'),
            responsePayload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $sender
     */
    private function mapContact(array $sender): ContactDto
    {
        return new ContactDto(
            externalContactId: (string) Arr::get($sender, 'id', 'viber-anonymous'),
            displayName: Arr::get($sender, 'name'),
            avatarUrl: Arr::get($sender, 'avatar'),
            meta: ['language' => Arr::get($sender, 'language')],
        );
    }
}
