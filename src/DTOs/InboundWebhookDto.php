<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class InboundWebhookDto extends Dto
{
    /**
     * @param  list<AttachmentDto>  $attachments
     * @param  array<string, mixed>|null  $normalizedPayload
     * @param  array<string, mixed>|null  $providerMetadata
     */
    public function __construct(
        public readonly string $externalEventId,
        public readonly string $eventType,
        public readonly ?string $messageType,
        public readonly ?string $interactionType,
        public readonly bool $isMessageEvent,
        public readonly bool $isStatusEvent,
        public readonly bool $isInteractionEvent,
        public readonly ContactDto $contact,
        public readonly ConversationContextDto $conversation,
        public readonly ?string $externalMessageId = null,
        public readonly ?string $providerStatus = null,
        public readonly ?string $content = null,
        public readonly array $attachments = [],
        public readonly ?array $normalizedPayload = null,
        public readonly ?array $providerMetadata = null,
        public readonly ?string $occurredAt = null,
    ) {}
}
