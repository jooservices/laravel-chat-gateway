<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;

/**
 * @mixin ChatWebhookEvent
 */
final class WebhookEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        unset($request);

        /** @var ChatWebhookEvent $webhookEvent */
        $webhookEvent = $this->resource;

        return [
            'id' => $webhookEvent->getKey(),
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'status' => $webhookEvent->status,
            'processed_at' => optional($webhookEvent->processed_at)?->toIso8601String(),
            'reason' => $webhookEvent->reason,
        ];
    }
}
