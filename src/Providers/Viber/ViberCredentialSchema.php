<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Providers\Viber;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JOOservices\LaravelChatGateway\Contracts\Providers\CredentialSchemaContract;

final class ViberCredentialSchema implements CredentialSchemaContract
{
    public function validateCredentials(array $credentials): void
    {
        $validator = Validator::make($credentials, [
            'auth_token' => ['required_without:access_token', 'string'],
            'access_token' => ['required_without:auth_token', 'string'],
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
        return ['auth_token'];
    }

    public function redactableKeys(): array
    {
        return ['auth_token', 'access_token'];
    }
}