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
        $secret = (string) $channel->webhook_secret;

        if ($secret === '') {
            return new VerificationResultDto(
                verified: false,
                reason: 'Channel has no webhook secret configured.',
            );
        }

        $signature = (string) $request->header('X-Viber-Content-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $verified = $signature !== '' && hash_equals($expected, $signature);

        return new VerificationResultDto(
            verified: $verified,
            reason: $verified ? null : 'Invalid Viber webhook signature.',
        );
    }
}
