<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use JOOservices\LaravelChatGateway\Contracts\Services\ConversationServiceContract;
use JOOservices\LaravelChatGateway\Models\ChatConversation;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelController\Http\Controllers\BaseApiController;

final class ConversationController extends BaseApiController
{
    public function __construct(
        private readonly ConversationServiceContract $conversationService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->conversationService->listConversations()->map(fn (ChatConversation $conversation): array => $this->transformConversation($conversation))->values()->all(),
        ]);
    }

    public function show(ChatConversation $conversation): JsonResponse
    {
        $resolved = $this->conversationService->getConversation((int) $conversation->getKey()) ?? $conversation;

        return response()->json([
            'success' => true,
            'data' => $this->transformConversation($resolved),
        ]);
    }

    public function messages(ChatConversation $conversation): JsonResponse
    {
        $resolved = $this->conversationService->getConversation((int) $conversation->getKey()) ?? $conversation;

        return response()->json([
            'success' => true,
            'data' => $this->conversationService->listMessages($resolved)->map(fn (ChatMessage $message): array => $this->transformConversationMessage($message))->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformConversation(ChatConversation $conversation): array
    {
        return [
            'id' => (int) $conversation->getKey(),
            'channel_id' => $conversation->channel_id,
            'contact_id' => $conversation->contact_id,
            'external_chat_id' => $conversation->external_chat_id,
            'chat_type' => $conversation->chat_type,
            'chat_title' => $conversation->chat_title,
            'chat_username' => $conversation->chat_username,
            'status' => $conversation->status,
            'started_at' => $conversation->started_at,
            'closed_at' => $conversation->closed_at,
            'last_message_at' => $conversation->last_message_at,
            'meta' => $conversation->meta,
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformConversationMessage(ChatMessage $message): array
    {
        return [
            'id' => (int) $message->getKey(),
            'provider' => $message->provider,
            'type' => $message->type,
            'direction' => $message->direction,
            'status' => $message->status,
            'external_message_id' => $message->external_message_id,
            'content' => $message->content,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    }
}
