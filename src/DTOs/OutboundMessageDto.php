<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class OutboundMessageDto extends Dto
{
    /**
     * @param  list<AttachmentDto>  $attachments
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly ?int $conversationId,
        public readonly ?int $channelId,
        public readonly ?string $channelKey,
        public readonly ?string $externalChatId,
        public readonly string $type,
        public readonly ?string $content = null,
        public readonly array $attachments = [],
        public readonly ?string $replyToMessageId = null,
        public readonly ?array $meta = null,
    ) {}
}
