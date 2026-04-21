<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $message_id
 * @property string $type
 * @property string|null $external_file_id
 * @property string|null $url
 * @property string|null $mime_type
 * @property string|null $file_name
 * @property int|null $file_size
 * @property array<string, mixed>|null $meta
 */
class ChatAttachment extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'message_id',
        'type',
        'external_file_id',
        'url',
        'mime_type',
        'file_name',
        'file_size',
        'meta',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo<ChatMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }
}
