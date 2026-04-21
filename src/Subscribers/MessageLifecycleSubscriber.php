<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Subscribers;

use Illuminate\Contracts\Events\Dispatcher;
use JOOservices\LaravelChatGateway\Contracts\Services\AuditEventBridgeContract;
use JOOservices\LaravelChatGateway\Events\IncomingMessageReceived;
use JOOservices\LaravelChatGateway\Events\MessageStatusUpdated;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageCreated;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageFailed;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageQueued;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageSent;

final class MessageLifecycleSubscriber
{
    public function __construct(
        private readonly AuditEventBridgeContract $bridge,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(IncomingMessageReceived::class, [$this, 'handle']);
        $events->listen(OutgoingMessageCreated::class, [$this, 'handle']);
        $events->listen(OutgoingMessageQueued::class, [$this, 'handle']);
        $events->listen(OutgoingMessageSent::class, [$this, 'handle']);
        $events->listen(OutgoingMessageFailed::class, [$this, 'handle']);
        $events->listen(MessageStatusUpdated::class, [$this, 'handle']);
    }

    public function handle(object $event): void
    {
        $this->bridge->handle($event);
    }
}
