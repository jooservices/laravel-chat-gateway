<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $provider
 * @property string $channel_key
 * @property string $name
 * @property string $status
 * @property bool $is_default
 * @property array<string, mixed>|null $credentials
 * @property array<string, mixed>|null $settings
 * @property string|null $webhook_secret
 * @property array<string, mixed>|null $meta
 */
class ChatChannel extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'channel_key',
        'name',
        'status',
        'is_default',
        'credentials',
        'settings',
        'webhook_secret',
        'meta',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'credentials' => 'array',
        'settings' => 'array',
        'meta' => 'array',
        'is_default' => 'bool',
    ];

    /**
     * @return HasMany<ChatConversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'channel_id');
    }

    /**
     * @return HasMany<ChatWebhookEvent, $this>
     */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(ChatWebhookEvent::class, 'channel_id');
    }

    /**
     * @return HasMany<ChatPollingState, $this>
     */
    public function pollingStates(): HasMany
    {
        return $this->hasMany(ChatPollingState::class, 'channel_id');
    }
}
