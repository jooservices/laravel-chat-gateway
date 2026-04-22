<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

interface CredentialSchemaContract
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): void;

    /**
     * @param  array<string, mixed>  $settings
     */
    public function validateSettings(array $settings): void;

    /**
     * @return list<string>
     */
    public function requiredCredentialKeys(): array;

    /**
     * @return list<string>
     */
    public function redactableKeys(): array;
}
