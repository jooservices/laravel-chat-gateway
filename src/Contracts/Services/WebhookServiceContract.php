<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatWebhookEvent;

interface WebhookServiceContract
{
    public function process(Request $request, string $provider, ?string $channelKey = null): ChatWebhookEvent;

    public function verify(Request $request, string $provider, ?string $channelKey = null): VerificationResultDto;
}
