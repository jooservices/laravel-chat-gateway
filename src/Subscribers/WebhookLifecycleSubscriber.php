<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Subscribers;

use Illuminate\Contracts\Events\Dispatcher;
use JOOservices\LaravelChatGateway\Contracts\Services\AuditEventBridgeContract;
use JOOservices\LaravelChatGateway\Events\WebhookDeduplicated;
use JOOservices\LaravelChatGateway\Events\WebhookReceived;
use JOOservices\LaravelChatGateway\Events\WebhookRejected;
use JOOservices\LaravelChatGateway\Events\WebhookVerified;

final class WebhookLifecycleSubscriber
{
    public function __construct(
        private readonly AuditEventBridgeContract $bridge,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(WebhookReceived::class, [$this, 'handle']);
        $events->listen(WebhookVerified::class, [$this, 'handle']);
        $events->listen(WebhookRejected::class, [$this, 'handle']);
        $events->listen(WebhookDeduplicated::class, [$this, 'handle']);
    }

    public function handle(object $event): void
    {
        $this->bridge->handle($event);
    }
}
