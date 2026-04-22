<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;
use JOOservices\LaravelChatGateway\Exceptions\ChannelNotFoundException;
use JOOservices\LaravelChatGateway\Exceptions\ChatGatewayException;
use JOOservices\LaravelChatGateway\Exceptions\UnsupportedProviderException;
use JOOservices\LaravelChatGateway\Http\Requests\Api\V1\RetryMessageRequest;
use JOOservices\LaravelChatGateway\Http\Requests\Api\V1\StoreMessageRequest;
use JOOservices\LaravelChatGateway\Models\ChatMessage;
use JOOservices\LaravelController\Http\Controllers\BaseApiController;

final class MessageController extends BaseApiController
{
    public function __construct(
        private readonly MessageServiceContract $messageService,
    ) {}

    public function store(StoreMessageRequest $request): JsonResponse
    {
        try {
            $message = $this->messageService->dispatchOutboundFromApi($request->validated());
        } catch (UnsupportedProviderException $exception) {
            return $this->clientError($exception->getMessage(), 422);
        } catch (ChannelNotFoundException $exception) {
            return $this->clientError($exception->getMessage(), 404);
        } catch (ChatGatewayException $exception) {
            return $this->clientError($exception->getMessage(), 422);
        }

        $resolved = $this->messageService->getMessage((int) $message->getKey()) ?? $message;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $resolved->getKey(),
                'status' => $resolved->status,
            ],
        ], 201);
    }

    public function show(ChatMessage $message): JsonResponse
    {
        $resolved = $this->messageService->getMessage((int) $message->getKey()) ?? $message;

        return response()->json([
            'success' => true,
            'data' => $this->transformMessage($resolved),
        ]);
    }

    public function retry(RetryMessageRequest $request, ChatMessage $message): JsonResponse
    {
        unset($request);

        try {
            $this->messageService->retry((int) $message->getKey());
        } catch (ChatGatewayException $exception) {
            return $this->clientError($exception->getMessage(), 422);
        }

        $resolved = $this->messageService->getMessage((int) $message->getKey()) ?? $message;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $resolved->getKey(),
                'status' => $resolved->status,
            ],
        ], 202);
    }

    private function clientError(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformMessage(ChatMessage $message): array
    {
        $latestStatusLog = $message->statusLogs->sortByDesc('id')->first();

        return [
            'id' => (int) $message->getKey(),
            'provider' => $message->provider,
            'type' => $message->type,
            'direction' => $message->direction,
            'status' => $message->status,
            'provider_status' => $latestStatusLog?->provider_status,
            'external_message_id' => $message->external_message_id,
            'content' => $message->content,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    }
}
