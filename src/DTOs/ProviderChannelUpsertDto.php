<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class ProviderChannelUpsertDto extends Dto
{
    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly string $channelKey,
        public readonly string $name,
        public readonly array $credentials,
        public readonly ?string $webhookSecret = null,
        public readonly array $settings = [],
        public readonly ?array $meta = null,
        public readonly bool $isDefault = false,
        public readonly string $status = 'active',
    ) {}
}