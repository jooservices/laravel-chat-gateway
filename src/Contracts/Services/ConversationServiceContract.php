<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Services;

use JOOservices\LaravelChatGateway\DTOs\InboundWebhookDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use JOOservices\LaravelChatGateway\Models\ChatConversation;

interface ConversationServiceContract
{
    public function resolve(ChatChannel $channel, ChatContact $contact, InboundWebhookDto $event): ChatConversation;

    public function close(ChatConversation $conversation): ChatConversation;
}
