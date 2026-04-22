<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class PollingRunResultDto extends Dto
{
    public function __construct(
        public readonly string $provider,
        public readonly string $channelKey,
        public readonly int $processedCount,
        public readonly int $deduplicatedCount,
        public readonly int $fetchedCount,
        public readonly int $failedCount,
        public readonly int $offset,
    ) {}
}
