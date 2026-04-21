<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class VerificationResultDto extends Dto
{
    public function __construct(
        public readonly bool $verified,
        public readonly ?string $reason = null,
        public readonly ?string $challenge = null,
    ) {}
}
