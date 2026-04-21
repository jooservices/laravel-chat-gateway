<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

use Illuminate\Http\Request;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageDto;
use JOOservices\LaravelChatGateway\DTOs\OutboundMessageResultDto;
use JOOservices\LaravelChatGateway\DTOs\ProviderCapabilitiesDto;
use JOOservices\LaravelChatGateway\DTOs\VerificationResultDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;

interface ChatProviderContract
{
    public function name(): string;

    public function capabilities(): ProviderCapabilitiesDto;

    public function parser(): InboundWebhookParserContract;

    public function verifier(): WebhookVerifierContract;

    public function sender(): OutboundMessageSenderContract;

    public function verify(Request $request, ChatChannel $channel): VerificationResultDto;

    public function send(ChatChannel $channel, OutboundMessageDto $message): OutboundMessageResultDto;
}
