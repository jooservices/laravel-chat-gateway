<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\WhatsApp;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\Contracts\Providers\ChatProviderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\InboundWebhookParserContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\OutboundMessageSenderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\WebhookVerifierContract;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\DTOs\ProviderCapabilitiesDto;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

final class WhatsAppProvider implements ChatProviderContract
{
    public function __construct(
        private readonly WhatsAppWebhookParser $parser,
        private readonly WhatsAppWebhookVerifier $verifier,
        private readonly WhatsAppMessageSender $sender,
    ) {}

    public function name(): string
    {
        return 'whatsapp';
    }

    public function capabilities(): ProviderCapabilitiesDto
    {
        return new ProviderCapabilitiesDto(true, true, true, true, true);
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

    public function verify(Request $request, ChatChannel $channel): VerificationResultDto
    {
        return $this->verifier->verify($request, $channel);
    }

    public function send(ChatChannel $channel, OutboundMessageDto $message): OutboundMessageResultDto
    {
        return $this->sender->send($channel, $message);
    }
}
