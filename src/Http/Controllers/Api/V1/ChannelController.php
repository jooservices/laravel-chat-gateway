<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderChannelServiceContract;
use JOOservices\LaravelChatGateway\Exceptions\UnsupportedProviderException;
use JOOservices\LaravelChatGateway\Http\Requests\Api\V1\StoreChannelRequest;
use JOOservices\LaravelChatGateway\Http\Requests\Api\V1\UpdateChannelRequest;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelController\Http\Controllers\BaseApiController;

final class ChannelController extends BaseApiController
{
    public function __construct(
        private readonly ProviderChannelServiceContract $providerChannelService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->providerChannelService->listChannels()->map(fn (ChatChannel $channel): array => $this->transformChannel($channel))->values()->all(),
        ]);
    }

    public function store(StoreChannelRequest $request): JsonResponse
    {
        try {
            $channel = $this->providerChannelService->registerChannelFromApi($request->validated());
        } catch (UnsupportedProviderException $exception) {
            return $this->clientError($exception->getMessage(), 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformChannel($channel),
        ], 201);
    }

    public function show(ChatChannel $channel): JsonResponse
    {
        $resolved = $this->providerChannelService->getChannel((int) $channel->getKey()) ?? $channel;

        return response()->json([
            'success' => true,
            'data' => $this->transformChannel($resolved),
        ]);
    }

    public function update(UpdateChannelRequest $request, ChatChannel $channel): JsonResponse
    {
        try {
            $channel = $this->providerChannelService->updateChannelFromApi($channel, $request->validated());
        } catch (UnsupportedProviderException $exception) {
            return $this->clientError($exception->getMessage(), 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformChannel($channel),
        ]);
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
    private function transformChannel(ChatChannel $channel): array
    {
        return [
            'id' => (int) $channel->getKey(),
            'provider' => $channel->provider,
            'channel_key' => $channel->channel_key,
            'name' => $channel->name,
            'status' => $channel->status,
            'is_default' => $channel->is_default,
            'settings' => $channel->settings,
            'meta' => $channel->meta,
            'has_credentials' => $this->hasCredentials($channel),
            'credential_keys' => $this->credentialKeys($channel),
            'webhook_secret_configured' => is_string($channel->webhook_secret) && $channel->webhook_secret !== '',
            'created_at' => $channel->created_at,
            'updated_at' => $channel->updated_at,
        ];
    }

    private function hasCredentials(ChatChannel $channel): bool
    {
        return is_array($channel->credentials) && $channel->credentials !== [];
    }

    /**
     * @return list<string>
     */
    private function credentialKeys(ChatChannel $channel): array
    {
        if (! is_array($channel->credentials)) {
            return [];
        }

        return array_map(
            static fn (int|string $key): string => (string) $key,
            array_keys($channel->credentials),
        );
    }
}
