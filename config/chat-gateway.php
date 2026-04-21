<?php

declare(strict_types=1);

return [
    'default_provider' => env('CHAT_GATEWAY_DEFAULT_PROVIDER', 'telegram'),

    'inbound' => [
        'default_mode' => env('CHAT_GATEWAY_INBOUND_DEFAULT_MODE', 'poll'),
    ],

    'database' => [
        'connection' => env('CHAT_GATEWAY_DB_CONNECTION'),
    ],

    'routes' => [
        'enabled' => env('CHAT_GATEWAY_ROUTES_ENABLED', true),
        'prefix' => env('CHAT_GATEWAY_ROUTE_PREFIX', 'chat-gateway'),
        'middleware' => ['api'],
    ],

    'webhooks' => [
        'store_headers' => true,
        'persist_raw_payload' => false,
        'verification_header_map' => [
            'telegram' => 'x-telegram-bot-api-secret-token',
            'viber' => 'x-viber-content-signature',
            'whatsapp' => 'x-hub-signature-256',
        ],
    ],

    'messages' => [
        'raw_payload_persistence' => false,
        'default_type' => 'text',
    ],

    'conversations' => [
        'default_status' => 'open',
        'close_on_statuses' => ['closed'],
    ],

    'queue' => [
        'enabled' => env('CHAT_GATEWAY_QUEUE_ENABLED', true),
        'connection' => env('CHAT_GATEWAY_QUEUE_CONNECTION'),
        'queues' => [
            'outbound' => env('CHAT_GATEWAY_QUEUE_OUTBOUND', 'chat-outbound'),
            'side_effects' => env('CHAT_GATEWAY_QUEUE_SIDE_EFFECTS', 'chat-side-effects'),
            'inbound_deferred' => env('CHAT_GATEWAY_QUEUE_INBOUND_DEFERRED', 'chat-inbound-deferred'),
        ],
        'outbound' => [
            'tries' => 3,
            'timeout' => 120,
            'backoff' => [10, 30, 60],
        ],
    ],

    'cache' => [
        'store' => env('CHAT_GATEWAY_CACHE_STORE', env('CACHE_STORE', 'redis')),
        'channel_ttl_seconds' => 300,
        'conversation_ttl_seconds' => 300,
        'webhook_dedupe_ttl_seconds' => 300,
        'poll_dedupe_ttl_seconds' => 300,
        'prefix' => 'chat_gateway',
    ],

    'events' => [
        'audit_enabled' => true,
        'sourcing_enabled' => true,
        'source' => 'laravel-chat-gateway',
        'schema_version' => 1,
    ],

    'providers' => [
        'telegram' => [
            'base_uri' => 'https://api.telegram.org',
            'timeout' => 15,
            'connect_timeout' => 5,
            'verify_ssl' => true,
            'inbound_mode' => env('CHAT_GATEWAY_TELEGRAM_INBOUND_MODE', env('CHAT_GATEWAY_INBOUND_DEFAULT_MODE', 'poll')),
            'polling' => [
                'enabled' => env('CHAT_GATEWAY_TELEGRAM_POLLING_ENABLED', true),
                'timeout' => env('CHAT_GATEWAY_TELEGRAM_POLLING_TIMEOUT', 30),
                'limit' => env('CHAT_GATEWAY_TELEGRAM_POLLING_LIMIT', 100),
                'allowed_updates' => ['message', 'callback_query', 'my_chat_member', 'chat_member'],
            ],
            'webhook' => [
                'enabled' => env('CHAT_GATEWAY_TELEGRAM_WEBHOOK_ENABLED', true),
                'secret_token' => env('CHAT_GATEWAY_TELEGRAM_WEBHOOK_SECRET_TOKEN'),
            ],
        ],
        'viber' => [
            'base_uri' => 'https://chatapi.viber.com',
            'timeout' => 15,
            'connect_timeout' => 5,
            'verify_ssl' => true,
            'inbound_mode' => env('CHAT_GATEWAY_VIBER_INBOUND_MODE', env('CHAT_GATEWAY_INBOUND_DEFAULT_MODE', 'poll')),
            'polling' => [
                'enabled' => false,
                'timeout' => 30,
                'limit' => 100,
                'allowed_updates' => [],
            ],
            'webhook' => [
                'enabled' => true,
                'secret_token' => env('CHAT_GATEWAY_VIBER_WEBHOOK_SECRET_TOKEN'),
            ],
        ],
        'whatsapp' => [
            'base_uri' => 'https://graph.facebook.com',
            'timeout' => 15,
            'connect_timeout' => 5,
            'verify_ssl' => true,
            'api_version' => 'v21.0',
            'inbound_mode' => env('CHAT_GATEWAY_WHATSAPP_INBOUND_MODE', env('CHAT_GATEWAY_INBOUND_DEFAULT_MODE', 'poll')),
            'polling' => [
                'enabled' => false,
                'timeout' => 30,
                'limit' => 100,
                'allowed_updates' => [],
            ],
            'webhook' => [
                'enabled' => true,
                'secret_token' => env('CHAT_GATEWAY_WHATSAPP_WEBHOOK_SECRET_TOKEN'),
            ],
        ],
    ],

    'logging' => [
        'privacy_mode' => env('CHAT_GATEWAY_PRIVACY_MODE', true),
        'redact_phone_numbers' => env('CHAT_GATEWAY_REDACT_PHONE_NUMBERS', true),
        'redacted_fields' => [
            'access_token',
            'authorization',
            'app_secret',
            'bot_token',
            'phone_number',
            'signature',
            'webhook_secret',
        ],
    ],
];
