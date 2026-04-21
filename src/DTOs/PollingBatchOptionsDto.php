<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class PollingBatchOptionsDto extends Dto
{
    /**
     * @param  list<string>|null  $allowedUpdates
     */
    public function __construct(
        public readonly ?string $channelKey = null,
        public readonly ?int $timeout = null,
        public readonly ?int $limit = null,
        public readonly ?array $allowedUpdates = null,
        public readonly bool $resetOffset = false,
    ) {}
}