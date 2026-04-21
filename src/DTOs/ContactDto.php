<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class ContactDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly string $externalContactId,
        public readonly ?string $externalUsername = null,
        public readonly ?string $displayName = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $avatarUrl = null,
        public readonly ?array $meta = null,
    ) {}
}
