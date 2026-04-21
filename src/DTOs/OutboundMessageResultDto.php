<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class OutboundMessageResultDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $responsePayload
     */
    public function __construct(
        public readonly bool $successful,
        public readonly string $status,
        public readonly ?string $externalMessageId = null,
        public readonly ?string $providerStatus = null,
        public readonly ?string $errorMessage = null,
        public readonly ?array $responsePayload = null,
    ) {}
}
