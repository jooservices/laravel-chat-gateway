<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Subscribers;

use Illuminate\Contracts\Events\Dispatcher;
use JOOservices\LaravelChatGateway\Contracts\Services\AuditEventBridgeContract;
use JOOservices\LaravelChatGateway\Events\ConversationClosed;
use JOOservices\LaravelChatGateway\Events\ConversationCreated;
use JOOservices\LaravelChatGateway\Events\ConversationUpdated;

final class ConversationLifecycleSubscriber
{
    public function __construct(
        private readonly AuditEventBridgeContract $bridge,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ConversationCreated::class, [$this, 'handle']);
        $events->listen(ConversationUpdated::class, [$this, 'handle']);
        $events->listen(ConversationClosed::class, [$this, 'handle']);
    }

    public function handle(object $event): void
    {
        $this->bridge->handle($event);
    }
}
