<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\DTOs;

use JOOservices\Dto\Core\Dto;

final class AttachmentDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $externalFileId = null,
        public readonly ?string $url = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $fileName = null,
        public readonly ?int $fileSize = null,
        public readonly ?array $meta = null,
    ) {}
}
