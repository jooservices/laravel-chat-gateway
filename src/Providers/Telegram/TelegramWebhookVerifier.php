<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Telegram;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\Contracts\Providers\WebhookVerifierContract;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class TelegramWebhookVerifier implements WebhookVerifierContract
{
    public function verify(Request $request, ChatChannel $channel): VerificationResultDto
    {
        $header = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($header === '') {
            $header = (string) $request->query('secret', '');
        }

        $verified = hash_equals((string) $channel->webhook_secret, $header);

        return new VerificationResultDto(
            verified: $verified,
            reason: $verified ? null : 'Invalid Telegram webhook secret.',
        );
    }
}
