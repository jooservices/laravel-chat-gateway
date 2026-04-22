<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\WhatsApp;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JOOservices\LaravelChatGateway\Contracts\Providers\CredentialSchemaContract;

final class WhatsAppCredentialSchema implements CredentialSchemaContract
{
    public function validateCredentials(array $credentials): void
    {
        $validator = Validator::make($credentials, [
            'access_token' => ['required', 'string'],
            'phone_number_id' => ['required', 'string'],
            'app_secret' => ['nullable', 'string'],
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
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function requiredCredentialKeys(): array
    {
        return ['access_token', 'phone_number_id'];
    }

    public function redactableKeys(): array
    {
        return ['access_token', 'app_secret'];
    }
}
