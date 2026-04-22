<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $conversation_id
 * @property string $provider
 * @property string $direction
 * @property string $type
 * @property string $status
 * @property string|null $external_message_id
 * @property int|string|null $reply_to_message_id
 * @property string|null $content
 * @property array<string, mixed>|null $normalized_payload
 * @property array<string, mixed>|null $raw_payload
 * @property string|null $error_message
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChatMessage extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'conversation_id',
        'provider',
        'direction',
        'type',
        'status',
        'external_message_id',
        'reply_to_message_id',
        'content',
        'normalized_payload',
        'raw_payload',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'normalized_payload' => 'array',
        'raw_payload' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ChatConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /**
     * @return HasMany<ChatAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ChatAttachment::class, 'message_id');
    }

    /**
     * @return HasMany<ChatMessageStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ChatMessageStatusLog::class, 'message_id');
    }
}
