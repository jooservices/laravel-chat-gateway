<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface WebhookVerifierContract
{
    public function verify(Request $request, ChatChannel $channel): VerificationResultDto;
}
