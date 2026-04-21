<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JOOservices\LaravelChatGateway\Contracts\Services\WebhookServiceContract;
use JOOservices\LaravelChatGateway\Exceptions\WebhookRejectedException;
use JOOservices\LaravelChatGateway\Http\Requests\WebhookRequest;
use JOOservices\LaravelChatGateway\Http\Resources\WebhookEventResource;
use JOOservices\LaravelController\Http\Controllers\BaseApiController;

final class WebhookController extends BaseApiController
{
    public function __construct(
        private readonly WebhookServiceContract $webhookService,
    ) {}

    public function store(WebhookRequest $request, string $provider, ?string $channelKey = null): JsonResponse
    {
        try {
            $event = $this->webhookService->process($request, $provider, $channelKey);

            return $this->accepted(new WebhookEventResource($event), 'Webhook accepted.');
        } catch (WebhookRejectedException $exception) {
            return $this->forbidden($exception->getMessage());
        }
    }

    public function verify(WebhookRequest $request, string $provider, ?string $channelKey = null): JsonResponse
    {
        try {
            $verification = $this->webhookService->verify($request, $provider, $channelKey);
        } catch (WebhookRejectedException $exception) {
            return $this->forbidden($exception->getMessage());
        }

        if ($verification->challenge !== null) {
            return $this->success(['challenge' => $verification->challenge], 'Webhook verified.');
        }

        return $this->success(['verified' => $verification->verified], 'Webhook verified.');
    }
}
