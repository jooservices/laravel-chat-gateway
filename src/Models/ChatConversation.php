<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $channel_id
 * @property int $contact_id
 * @property string $external_chat_id
 * @property string|null $chat_type
 * @property string|null $chat_title
 * @property string|null $chat_username
 * @property string $status
 * @property Carbon|null $started_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $last_message_at
 * @property array<string, mixed>|null $meta
 * @property ChatChannel $channel
 * @property ChatContact $contact
 */
class ChatConversation extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel_id',
        'contact_id',
        'external_chat_id',
        'chat_type',
        'chat_title',
        'chat_username',
        'status',
        'started_at',
        'closed_at',
        'last_message_at',
        'meta',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ChatChannel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    /**
     * @return BelongsTo<ChatContact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(ChatContact::class, 'contact_id');
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}
