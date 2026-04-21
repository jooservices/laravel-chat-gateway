<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit\Models;

use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class ChatGatewayModelTest extends TestCase
{
    public function test_it_uses_the_configured_operational_connection_when_present(): void
    {
        config()->set('chat-gateway.database.connection', 'operational-mysql');

        $model = new ChatMessage();

        $this->assertSame('operational-mysql', $model->getConnectionName());
    }

    public function test_it_falls_back_to_the_default_connection_when_not_configured(): void
    {
        config()->set('chat-gateway.database.connection', null);

        $model = new ChatMessage();

        $this->assertNull($model->getConnectionName());
    }
}