<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use JOOservices\LaravelChatGateway\Exceptions\WebhookRejectedException;
use JOOservices\LaravelChatGateway\Http\Requests\Api\V1\TelegramWebhookRequest;
use JOOservices\LaravelChatGateway\Http\Requests\Api\V1\ViberWebhookRequest;
use JOOservices\LaravelChatGateway\Http\Requests\Api\V1\WhatsAppWebhookRequest;
use JOOservices\LaravelChatGateway\Services\WebhookService;
use JOOservices\LaravelController\Http\Controllers\BaseApiController;

final class WebhookController extends BaseApiController
{
    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    public function telegram(TelegramWebhookRequest $request): JsonResponse
    {
        return $this->processWebhook($request, 'telegram');
    }

    public function whatsapp(WhatsAppWebhookRequest $request): JsonResponse
    {
        return $this->processWebhook($request, 'whatsapp');
    }

    public function viber(ViberWebhookRequest $request): JsonResponse
    {
        return $this->processWebhook($request, 'viber');
    }

    private function processWebhook(TelegramWebhookRequest|WhatsAppWebhookRequest|ViberWebhookRequest $request, string $provider): JsonResponse
    {
        try {
            $event = $this->webhookService->process($request, $provider);
        } catch (WebhookRejectedException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'webhook_event_id' => (int) $event->getKey(),
                'status' => $event->status,
            ],
        ]);
    }
}
