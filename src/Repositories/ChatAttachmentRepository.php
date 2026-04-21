<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatAttachmentRepositoryContract;
use JOOservices\LaravelChatGateway\DTOs\AttachmentDto;
use JOOservices\LaravelChatGateway\Models\ChatAttachment;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatAttachmentRepository extends EloquentRepository implements ChatAttachmentRepositoryContract
{
    public function __construct(ChatAttachment $model)
    {
        parent::__construct($model);
    }

    public function createFromDto(ChatMessage $message, AttachmentDto $attachment): ChatAttachment
    {
        /** @var ChatAttachment $record */
        $record = $message->attachments()->create([
            'type' => $attachment->type,
            'external_file_id' => $attachment->externalFileId,
            'url' => $attachment->url,
            'mime_type' => $attachment->mimeType,
            'file_name' => $attachment->fileName,
            'file_size' => $attachment->fileSize,
            'meta' => $attachment->meta,
        ]);

        return $record;
    }
}
