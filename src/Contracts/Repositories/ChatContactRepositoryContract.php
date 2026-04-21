<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Repositories;

use JOOservices\LaravelChatGateway\DTOs\ContactDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;

interface ChatContactRepositoryContract
{
    public function upsertFromDto(string $provider, ?ChatChannel $channel, ContactDto $contact): ChatContact;
}
