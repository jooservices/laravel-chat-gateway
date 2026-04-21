<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Viber;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\Contracts\Providers\WebhookVerifierContract;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class ViberWebhookVerifier implements WebhookVerifierContract
{
    public function verify(Request $request, ChatChannel $channel): VerificationResultDto
    {
        $signature = (string) $request->header('X-Viber-Content-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $channel->webhook_secret);
        $verified = $signature !== '' && hash_equals($expected, $signature);

        return new VerificationResultDto(
            verified: $verified,
            reason: $verified ? null : 'Invalid Viber webhook signature.',
        );
    }
}
