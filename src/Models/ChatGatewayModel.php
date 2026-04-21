<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ChatGatewayModel extends Model
{
    public function getConnectionName(): ?string
    {
        $configuredConnection = config('chat-gateway.database.connection');

        if (is_string($configuredConnection) && $configuredConnection !== '') {
            return $configuredConnection;
        }

        return parent::getConnectionName();
    }
}