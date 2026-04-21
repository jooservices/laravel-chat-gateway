<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $channel_id
 * @property string $provider
 * @property string $transport
 * @property string|null $external_event_id
 * @property string $event_type
 * @property string $status
 * @property string $payload_hash
 * @property array<string, mixed>|null $headers
 * @property array<string, mixed>|null $payload
 * @property string|null $reason
 * @property Carbon|null $processed_at
 */
class ChatWebhookEvent extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel_id',
        'provider',
        'transport',
        'external_event_id',
        'event_type',
        'status',
        'payload_hash',
        'headers',
        'payload',
        'reason',
        'processed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ChatChannel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }
}
