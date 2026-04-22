<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Contracts\Providers;

interface SupportsCredentialSchemaContract
{
    public function credentialSchema(): CredentialSchemaContract;
}
