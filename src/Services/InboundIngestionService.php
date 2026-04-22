<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatContactRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatMessageRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ConversationServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundIngestionServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;
use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class InboundIngestionService implements InboundIngestionServiceContract
{
    public function __construct(
        private readonly ChatContactRepositoryContract $contactRepository,
        private readonly ConversationServiceContract $conversationService,
        private readonly MessageServiceContract $messageService,
        private readonly ChatMessageRepositoryContract $messageRepository,
    ) {}

    public function ingest(string $provider, ChatChannel $channel, InboundWebhookDto $parsed, ?array $rawPayload = null): void
    {
        DB::connection($channel->getConnectionName())->transaction(function () use ($provider, $channel, $parsed, $rawPayload): void {
            $contact = $this->contactRepository->upsertFromDto($provider, $channel, $parsed->contact);
            $conversation = $this->conversationService->resolve($channel, $contact, $parsed);

            $message = $this->messageService->createInbound($conversation, $parsed, $rawPayload);

            if ($parsed->isStatusEvent && $parsed->externalMessageId !== null && $message === null) {
                $storedMessage = $this->messageRepository->findByProviderMessageId($provider, $parsed->externalMessageId);

                if ($storedMessage !== null) {
                    $status = $parsed->eventType === 'read_status' ? 'read' : 'delivered';
                    $this->messageService->updateStatus($storedMessage, $status, $parsed->providerStatus, $parsed->normalizedPayload);
                }
            }

            if ($parsed->eventType === 'system' && in_array(Arr::get($parsed->providerMetadata ?? [], 'conversation_status'), ['closed'], true)) {
                $this->conversationService->close($conversation);
            }
        });
    }
}
