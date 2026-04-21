<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Helpers;

final class MaskingHelper
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function redact(array $payload, array $fields, bool $redactPhoneNumbers = true): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $fields, true)) {
                $sanitized[$key] = self::maskValue($value);

                continue;
            }

            if ($redactPhoneNumbers && str_contains($normalizedKey, 'phone')) {
                $sanitized[$key] = self::maskPhoneNumber($value);

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::redact($value, $fields, $redactPhoneNumbers);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private static function maskValue(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '[redacted]';
        }

        return substr($value, 0, 2).str_repeat('*', max(strlen($value) - 4, 3)).substr($value, -2);
    }

    private static function maskPhoneNumber(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_replace('/\d(?=\d{2})/', '*', $value) ?? $value;
    }
}
