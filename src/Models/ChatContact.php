<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $provider
 * @property int $channel_id
 * @property string $external_contact_id
 * @property string|null $external_username
 * @property string|null $display_name
 * @property string|null $phone_number
 * @property string|null $avatar_url
 * @property array<string, mixed>|null $meta
 */
class ChatContact extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'channel_id',
        'external_contact_id',
        'external_username',
        'display_name',
        'phone_number',
        'avatar_url',
        'meta',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo<ChatChannel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    /**
     * @return HasMany<ChatConversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'contact_id');
    }
}
