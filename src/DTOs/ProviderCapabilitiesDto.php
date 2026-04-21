<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class ProviderCapabilitiesDto extends Dto
{
    public function __construct(
        public readonly bool $supportsText,
        public readonly bool $supportsImageFile,
        public readonly bool $supportsButtonInteraction,
        public readonly bool $supportsDeliveryReceipt,
        public readonly bool $supportsReadReceipt,
    ) {}
}
