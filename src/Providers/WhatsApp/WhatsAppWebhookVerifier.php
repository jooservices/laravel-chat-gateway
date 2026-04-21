<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\WhatsApp;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\Contracts\Providers\WebhookVerifierContract;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class WhatsAppWebhookVerifier implements WebhookVerifierContract
{
    public function verify(Request $request, ChatChannel $channel): VerificationResultDto
    {
        if ($request->isMethod('get')) {
            $verifyToken = (string) $request->query('hub_verify_token', '');
            $verified = hash_equals((string) $channel->webhook_secret, $verifyToken);

            return new VerificationResultDto(
                verified: $verified,
                reason: $verified ? null : 'Invalid WhatsApp verify token.',
                challenge: $verified ? (string) $request->query('hub_challenge', '') : null,
            );
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $secret = (string) ($channel->credentials['app_secret'] ?? $channel->webhook_secret);
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        $verified = $signature !== '' && hash_equals($expected, $signature);

        return new VerificationResultDto(
            verified: $verified,
            reason: $verified ? null : 'Invalid WhatsApp webhook signature.',
        );
    }
}
