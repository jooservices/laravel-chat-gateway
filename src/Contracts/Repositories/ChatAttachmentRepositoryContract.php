<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\DTOs\AttachmentDto;
use JOOservices\LaravelChatGateway\Models\ChatAttachment;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

interface ChatAttachmentRepositoryContract
{
    public function createFromDto(ChatMessage $message, AttachmentDto $attachment): ChatAttachment;
}
