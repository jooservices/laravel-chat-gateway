<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $message_id
 * @property string|null $old_status
 * @property string $new_status
 * @property string|null $provider_status
 * @property array<string, mixed>|null $payload
 */
class ChatMessageStatusLog extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'message_id',
        'old_status',
        'new_status',
        'provider_status',
        'payload',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * @return BelongsTo<ChatMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }
}
