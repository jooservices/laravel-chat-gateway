<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class ConversationContextDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly string $externalChatId,
        public readonly ?string $status = null,
        public readonly ?array $meta = null,
        public readonly ?string $chatType = null,
        public readonly ?string $chatTitle = null,
        public readonly ?string $chatUsername = null,
    ) {}
}
