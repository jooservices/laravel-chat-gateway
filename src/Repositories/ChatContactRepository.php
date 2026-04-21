<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Repositories;

use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatContactRepositoryContract;
use JOOservices\LaravelChatGateway\DTOs\ContactDto;
use JOOservices\LaravelChatGateway\Models\ChatChannel;
use JOOservices\LaravelChatGateway\Models\ChatContact;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;

final class ChatContactRepository extends EloquentRepository implements ChatContactRepositoryContract
{
    public function __construct(ChatContact $model)
    {
        parent::__construct($model);
    }

    public function upsertFromDto(string $provider, ?ChatChannel $channel, ContactDto $contact): ChatContact
    {
        /** @var ChatContact $record */
        $record = $this->newQuery()->updateOrCreate(
            [
                'provider' => $provider,
                'channel_id' => $channel?->getKey(),
                'external_contact_id' => $contact->externalContactId,
            ],
            [
                'external_username' => $contact->externalUsername,
                'display_name' => $contact->displayName,
                'phone_number' => $contact->phoneNumber,
                'avatar_url' => $contact->avatarUrl,
                'meta' => $contact->meta,
            ]
        );

        return $record;
    }
}
