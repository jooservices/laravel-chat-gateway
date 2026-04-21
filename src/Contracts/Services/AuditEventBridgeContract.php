<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

interface AuditEventBridgeContract
{
    public function handle(object $event): void;
}
