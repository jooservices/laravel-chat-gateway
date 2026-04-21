<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class PollingFetchResultDto extends Dto
{
    /**
     * @param  list<array<string, mixed>>  $updates
     */
    public function __construct(
        public readonly array $updates,
    ) {}
}