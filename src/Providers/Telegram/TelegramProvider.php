<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Telegram;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\Contracts\Providers\InboundWebhookParserContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\OutboundMessageSenderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\PollingCapableProviderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\PollingUpdateFetcherContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\WebhookVerifierContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\DTOs\ProviderCapabilitiesDto;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class TelegramProvider implements PollingCapableProviderContract
{
    public function __construct(
        private readonly TelegramWebhookParser $parser,
        private readonly TelegramWebhookVerifier $verifier,
        private readonly TelegramMessageSender $sender,
        private readonly TelegramUpdateFetcher $updateFetcher,
    ) {}

    public function name(): string
    {
        return 'telegram';
    }

    public function capabilities(): ProviderCapabilitiesDto
    {
        return new ProviderCapabilitiesDto(true, true, true, false, false);
    }

    public function parser(): InboundWebhookParserContract
    {
        return $this->parser;
    }

    public function verifier(): WebhookVerifierContract
    {
        return $this->verifier;
    }

    public function sender(): OutboundMessageSenderContract
    {
        return $this->sender;
    }

    public function pollingFetcher(): PollingUpdateFetcherContract
    {
        return $this->updateFetcher;
    }

    public function verify(Request $request, ChatChannel $channel): VerificationResultDto
    {
        return $this->verifier->verify($request, $channel);
    }

    public function send(ChatChannel $channel, OutboundMessageDto $message): OutboundMessageResultDto
    {
        return $this->sender->send($channel, $message);
    }
}
