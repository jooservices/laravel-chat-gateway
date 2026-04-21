<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Telegram;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JOOservices\LaravelChatGateway\Contracts\Providers\CredentialSchemaContract;

final class TelegramCredentialSchema implements CredentialSchemaContract
{
    public function validateCredentials(array $credentials): void
    {
        $validator = Validator::make($credentials, [
            'bot_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function validateSettings(array $settings): void
    {
        $validator = Validator::make($settings, [
            'inbound_mode' => ['nullable', 'string', 'in:poll,callback'],
            'webhook' => ['nullable', 'array'],
            'webhook.enabled' => ['nullable', 'boolean'],
            'polling' => ['nullable', 'array'],
            'polling.enabled' => ['nullable', 'boolean'],
            'polling.allowed_updates' => ['nullable', 'array'],
            'polling.allowed_updates.*' => ['string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function requiredCredentialKeys(): array
    {
        return ['bot_token'];
    }

    public function redactableKeys(): array
    {
        return ['bot_token'];
    }
}