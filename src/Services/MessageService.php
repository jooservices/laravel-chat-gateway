<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatAttachmentRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatContactRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatConversationRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatMessageRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatMessageStatusLogRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ChannelServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;
use JOOservices\LaravelChatGateway\DTOs\ContactDto;
use JOOservices\LaravelChatGateway\DTOs\ConversationContextDto;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\Events\IncomingMessageReceived;
use JOOservices\LaravelChatGateway\Events\MessageStatusUpdated;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageCreated;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageFailed;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageQueued;
use JOOservices\LaravelChatGateway\Events\OutgoingMessageSent;
use JOOservices\LaravelChatGateway\Exceptions\ChatGatewayException;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;

final class MessageService implements MessageServiceContract
{
    public function __construct(
        private readonly ChannelServiceContract $channelService,
        private readonly ChatContactRepositoryContract $contactRepository,
        private readonly ChatConversationRepositoryContract $conversationRepository,
        private readonly ChatMessageRepositoryContract $messageRepository,
        private readonly ChatAttachmentRepositoryContract $attachmentRepository,
        private readonly ChatMessageStatusLogRepositoryContract $statusLogRepository,
        private readonly ChatGatewayManager $manager,
        private readonly Dispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>|null  $rawPayload
     */
    public function createInbound(ChatConversation $conversation, InboundWebhookDto $event, ?array $rawPayload = null): ?ChatMessage
    {
        if (! $event->isMessageEvent) {
            return null;
        }

        $message = $this->messageRepository->create([
            'conversation_id' => $conversation->getKey(),
            'provider' => $conversation->channel->provider,
            'direction' => 'inbound',
            'type' => $event->messageType ?? config('chat-gateway.messages.default_type', 'text'),
            'status' => 'sent',
            'external_message_id' => $event->externalMessageId,
            'content' => $event->content,
            'normalized_payload' => $event->normalizedPayload,
            'raw_payload' => config('chat-gateway.messages.raw_payload_persistence', false) ? $rawPayload : null,
            'sent_at' => CarbonImmutable::now(),
        ]);

        foreach ($event->attachments as $attachment) {
            $this->attachmentRepository->createFromDto($message, $attachment);
        }

        $this->statusLogRepository->createLog($message, null, 'sent', $event->providerStatus, $event->normalizedPayload);
        $this->events->dispatch(new IncomingMessageReceived($message, $event));

        return $message;
    }

    public function send(OutboundMessageDto $message): OutboundMessageResultDto
    {
        $channel = $this->channelService->resolveOutbound($message);
        $provider = $this->manager->providerForChannel($channel);
        $capabilities = $provider->capabilities();
        $conversation = $message->conversationId !== null
            ? $this->conversationRepository->findById($message->conversationId)
            : null;

        if ($message->conversationId !== null && $conversation === null) {
            throw new ChatGatewayException('Conversation channel could not be resolved.');
        }

        $effectiveExternalChatId = $message->externalChatId ?? $conversation?->external_chat_id;

        if ($conversation === null && $effectiveExternalChatId !== null && $effectiveExternalChatId !== '') {
            $conversation = $this->bootstrapOutboundConversation($channel, $effectiveExternalChatId, $message->meta);
        }

        if ($conversation === null) {
            throw new ChatGatewayException('Outbound messages require either an existing conversation or an external chat id.');
        }

        if ($message->type === 'text' && ! $capabilities->supportsText) {
            throw new ChatGatewayException('Provider does not support text messages.');
        }

        if (in_array($message->type, ['image', 'file'], true) && ! $capabilities->supportsImageFile) {
            throw new ChatGatewayException('Provider does not support file messages.');
        }

        if ($message->type === 'button' && ! $capabilities->supportsButtonInteraction) {
            throw new ChatGatewayException('Provider does not support interaction messages.');
        }

        $resolvedMessage = new OutboundMessageDto(
            conversationId: (int) $conversation->getKey(),
            channelId: $message->channelId,
            channelKey: $message->channelKey,
            externalChatId: $effectiveExternalChatId,
            type: $message->type,
            content: $message->content,
            attachments: $message->attachments,
            replyToMessageId: $message->replyToMessageId,
            meta: $message->meta,
        );

        $stored = $this->messageRepository->create([
            'conversation_id' => $conversation->getKey(),
            'provider' => $channel->provider,
            'direction' => 'outbound',
            'type' => $message->type,
            'status' => config('chat-gateway.queue.outbound_enabled', false) ? 'queued' : 'pending',
            'reply_to_message_id' => $message->replyToMessageId,
            'content' => $message->content,
            'normalized_payload' => $resolvedMessage->toArray(),
        ]);

        $this->events->dispatch(new OutgoingMessageCreated($stored));

        if ((bool) config('chat-gateway.queue.outbound_enabled', false)) {
            $this->events->dispatch(new OutgoingMessageQueued($stored));
        }

        $result = $provider->send($channel, $resolvedMessage);

        if ($result->successful) {
            $updated = $this->messageRepository->updateMessage($stored, [
                'status' => $result->status,
                'external_message_id' => $result->externalMessageId,
                'raw_payload' => $result->responsePayload,
                'sent_at' => CarbonImmutable::now(),
            ]);

            $this->statusLogRepository->createLog($updated, $stored->status, $result->status, $result->providerStatus, $result->responsePayload);
            $this->events->dispatch(new OutgoingMessageSent($updated, $result));

            return $result;
        }

        $updated = $this->messageRepository->updateMessage($stored, [
            'status' => 'failed',
            'error_message' => $result->errorMessage,
            'raw_payload' => $result->responsePayload,
            'failed_at' => CarbonImmutable::now(),
        ]);

        $this->statusLogRepository->createLog($updated, $stored->status, 'failed', $result->providerStatus, $result->responsePayload);
        $this->events->dispatch(new OutgoingMessageFailed($updated, $result->errorMessage ?? 'Outbound delivery failed.', $result->responsePayload));

        return $result;
    }

    public function updateStatus(ChatMessage $message, string $newStatus, ?string $providerStatus = null, ?array $payload = null): ChatMessage
    {
        $oldStatus = $message->status;

        $attributes = [
            'status' => $newStatus,
        ];

        if ($newStatus === 'delivered') {
            $attributes['delivered_at'] = CarbonImmutable::now();
        }

        if ($newStatus === 'read') {
            $attributes['read_at'] = CarbonImmutable::now();
        }

        if ($newStatus === 'failed') {
            $attributes['failed_at'] = CarbonImmutable::now();
        }

        $updated = $this->messageRepository->updateMessage($message, $attributes);
        $this->statusLogRepository->createLog($updated, $oldStatus, $newStatus, $providerStatus, $payload);
        $this->events->dispatch(new MessageStatusUpdated($updated, $oldStatus, $newStatus, $providerStatus, $payload));

        return $updated;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function bootstrapOutboundConversation(ChatChannel $channel, string $externalChatId, ?array $meta = null): ChatConversation
    {
        $existing = $this->conversationRepository->findByExternalChatId((int) $channel->getKey(), $externalChatId);

        if ($existing !== null) {
            return $existing;
        }

        $contact = $this->contactRepository->upsertFromDto(
            $channel->provider,
            $channel,
            new ContactDto(
                externalContactId: $externalChatId,
                displayName: is_string($meta['contact_display_name'] ?? null) ? $meta['contact_display_name'] : null,
                meta: [
                    'synthetic' => true,
                    'source' => 'outbound-bootstrap',
                ],
            ),
        );

        return $this->conversationRepository->createOrUpdate(
            $channel,
            $contact,
            new ConversationContextDto(
                externalChatId: $externalChatId,
                meta: [
                    'synthetic' => true,
                    'source' => 'outbound-bootstrap',
                ],
            ),
        );
    }
}
