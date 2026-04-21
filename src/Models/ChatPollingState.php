<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

/**
 * @property int $id
 * @property string $provider
 * @property int|null $channel_id
 * @property int $offset
 * @property array<string, mixed>|null $meta
 */
class ChatPollingState extends ChatGatewayModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'channel_id',
        'offset',
        'meta',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'offset' => 'int',
        'meta' => 'array',
    ];
}