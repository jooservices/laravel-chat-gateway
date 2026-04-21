<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Carbon\CarbonImmutable;
use JOOservices\LaravelChatGateway\Contracts\Services\AuditEventBridgeContract;
use JOOservices\LaravelChatGateway\Events\ConversationClosed;
use JOOservices\LaravelChatGateway\Events\ConversationCreated;
use JOOservices\LaravelChatGateway\Events\IncomingMessageReceived;
use JOOservices\LaravelChatGateway\Events\MessageStatusUpdated;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageCreated;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageFailed;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageSent;
use JOOservices\LaravelChatGateway\Events\WebhookDeduplicated;
use JOOservices\LaravelChatGateway\Events\WebhookReceived;
use JOOservices\LaravelChatGateway\Events\WebhookRejected;
use JOOservices\LaravelChatGateway\Events\WebhookVerified;
use JooServices\LaravelEvents\EventLog\EventLogAction;
use JooServices\LaravelEvents\EventService;

final class AuditEventBridge implements AuditEventBridgeContract
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function handle(object $event): void
    {
        if ((bool) config('chat-gateway.events.audit_enabled', true) && $this->shouldAudit($event)) {
            $this->logAudit($event);
        }

        if ((bool) config('chat-gateway.events.sourcing_enabled', true) && $this->shouldSource($event)) {
            $this->storeSource($event);
        }
    }

    private function shouldAudit(object $event): bool
    {
        return $event instanceof WebhookReceived
            || $event instanceof WebhookVerified
            || $event instanceof WebhookRejected
            || $event instanceof WebhookDeduplicated
            || $event instanceof OutgoingMessageFailed
            || $event instanceof OutgoingMessageSent
            || $event instanceof MessageStatusUpdated
            || $event instanceof ConversationCreated
            || $event instanceof ConversationClosed;
    }

    private function shouldSource(object $event): bool
    {
        return $event instanceof IncomingMessageReceived
            || $event instanceof OutgoingMessageCreated
            || $event instanceof OutgoingMessageSent
            || $event instanceof MessageStatusUpdated
            || $event instanceof ConversationCreated
            || $event instanceof ConversationClosed;
    }

    private function logAudit(object $event): void
    {
        if ($event instanceof WebhookReceived) {
            $this->eventService->logChange(
                'chat_webhook_events',
                (string) $event->webhookEvent->getKey(),
                EventLogAction::CREATED,
                [],
                $event->webhookEvent->toArray(),
                ['status' => ['old' => null, 'new' => $event->webhookEvent->status]],
                $this->meta('webhook_received'),
            );

            return;
        }

        if ($event instanceof WebhookVerified || $event instanceof WebhookRejected || $event instanceof WebhookDeduplicated) {
            $action = $event instanceof WebhookVerified ? EventLogAction::STATUS_CHANGED : EventLogAction::CORRECTED;
            $model = $event->webhookEvent;

            $this->eventService->logChange(
                'chat_webhook_events',
                (string) $model->getKey(),
                $action,
                [],
                $model->toArray(),
                ['status' => ['old' => null, 'new' => $model->status]],
                $this->meta(strtolower(class_basename($event))),
            );

            return;
        }

        if ($event instanceof OutgoingMessageSent || $event instanceof OutgoingMessageFailed || $event instanceof MessageStatusUpdated) {
            $message = $event->message;
            $oldStatus = $event instanceof MessageStatusUpdated ? $event->oldStatus : null;
            $newStatus = $event instanceof MessageStatusUpdated ? $event->newStatus : $message->status;

            $this->eventService->logChange(
                'chat_messages',
                (string) $message->getKey(),
                EventLogAction::STATUS_CHANGED,
                ['status' => $oldStatus],
                ['status' => $newStatus],
                ['status' => ['old' => $oldStatus, 'new' => $newStatus]],
                $this->meta(strtolower(class_basename($event))),
            );

            return;
        }

        if ($event instanceof ConversationCreated || $event instanceof ConversationClosed) {
            $conversation = $event->conversation;
            $action = $event instanceof ConversationCreated ? EventLogAction::CREATED : EventLogAction::STATUS_CHANGED;

            $this->eventService->logChange(
                'chat_conversations',
                (string) $conversation->getKey(),
                $action,
                [],
                $conversation->toArray(),
                ['status' => ['old' => null, 'new' => $conversation->status]],
                $this->meta(strtolower(class_basename($event))),
            );
        }
    }

    private function storeSource(object $event): void
    {
        if ($event instanceof IncomingMessageReceived) {
            $aggregateId = (string) $event->message->conversation_id;
            $payload = [
                'message_id' => $event->message->getKey(),
                'provider' => $event->message->provider,
                'direction' => $event->message->direction,
                'type' => $event->message->type,
                'status' => $event->message->status,
                'content' => $event->message->content,
                'normalized_payload' => $event->message->normalized_payload,
            ];

            $this->eventService->storeEvent($event, $payload, $aggregateId, auth()->id(), CarbonImmutable::now(), $this->meta('incoming_message_received'));

            return;
        }

        if ($event instanceof OutgoingMessageCreated || $event instanceof OutgoingMessageSent || $event instanceof MessageStatusUpdated) {
            $message = $event->message;

            $this->eventService->storeEvent(
                $event,
                $message->toArray(),
                (string) $message->conversation_id,
                auth()->id(),
                CarbonImmutable::now(),
                $this->meta(strtolower(class_basename($event))),
            );

            return;
        }

        if ($event instanceof ConversationCreated || $event instanceof ConversationClosed) {
            $conversation = $event->conversation;

            $this->eventService->storeEvent(
                $event,
                $conversation->toArray(),
                (string) $conversation->getKey(),
                auth()->id(),
                CarbonImmutable::now(),
                $this->meta(strtolower(class_basename($event))),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(string $reasonCode): array
    {
        return [
            'source' => config('chat-gateway.events.source', 'laravel-chat-gateway'),
            'reason_code' => $reasonCode,
            'schema_version' => config('chat-gateway.events.schema_version', 1),
        ];
    }
}
