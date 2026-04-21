<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Tests\Unit;

use JOOservices\LaravelChatGateway\Helpers\MaskingHelper;
use JOOservices\LaravelChatGateway\Tests\TestCase;

final class MaskingHelperTest extends TestCase
{
    public function test_it_redacts_secret_fields_and_phone_numbers(): void
    {
        $result = MaskingHelper::redact([
            'access_token' => 'super-secret-token',
            'phone_number' => '84901234567',
            'nested' => ['authorization' => 'Bearer abc123'],
        ], ['access_token', 'authorization'], true);

        $this->assertStringContainsString('*', $result['access_token']);
        $this->assertStringContainsString('*', $result['phone_number']);
        $this->assertStringContainsString('*', $result['nested']['authorization']);
    }
}
