# JOOservices Laravel Chat Gateway

Laravel-first multi-provider chat gateway for Telegram, Viber, and WhatsApp.

This package is meant to be installed directly into a Laravel application. The `_sandbox/laravel-app` folder in this repository is only a local integration host used to test the package through a Composer path repository.

This package provides:

- inbound webhook receiving and verification
- inbound polling with persisted offsets
- Redis plus database deduplication
- Redis queue support for outbound delivery with Horizon-friendly shared queues
- REST API routes under `/api/v1/chat-gateway`
- payload normalization through DTOs
- operational persistence in MySQL or MariaDB
- outbound message sending
- message status updates
- runtime Laravel events
- MongoDB audit and event-sourcing bridge through `jooservices/laravel-events`

## Requirements

- PHP `^8.5`
- Laravel `^12.0`
- MySQL or MariaDB for operational tables
- Redis for cache and dedupe
- MongoDB for `jooservices/laravel-events`

## Install

```bash
composer require jooservices/laravel-chat-gateway
```

The package works as a normal Laravel package in your own application. You do not need `_sandbox/laravel-app` to use it in production or in another host project.

Optionally publish config and migrations:

```bash
php artisan vendor:publish --tag=chat-gateway-config
php artisan vendor:publish --tag=chat-gateway-migrations
```

## Quick start

1. Configure `config/chat-gateway.php`.
2. Create one or more `chat_channels` records with provider credentials plus channel `settings` for inbound mode.
3. Use `php artisan gateway:poll telegram --once` for the default inbound mode.
4. If you opt into callback mode for a channel, point the provider webhook URL to the fixed provider API route, for example `/api/v1/chat-gateway/webhooks/telegram`.
5. Send outbound messages through `MessageService`, `ChatGatewayManager`, or `POST /api/v1/chat-gateway/messages`.

Inbound defaults:

- default inbound mode is `poll`
- callback or webhook mode remains supported per channel
- Telegram channels should not run `getUpdates` polling and webhook delivery carelessly at the same time

## Local sandbox

This repository includes a local-only Laravel 12 sandbox app at `_sandbox/laravel-app` for testing the package through a Composer path repository.

- the sandbox is intentionally inside this package repository
- `_sandbox/` is git-ignored and must remain local-only
- the sandbox is wired for MySQL or MariaDB, MongoDB, Redis, and Telegram-first testing

Outbound delivery can run inline or through Redis queues. When `chat-gateway.queue.enabled` is true, `POST /api/v1/chat-gateway/messages` stores the outbound message as `queued` and dispatches the shared `DispatchChatMessageJob` to `chat-outbound`. When it is false, the same service path sends inline through the provider sender.

Channel API responses intentionally do not expose raw `credentials` or `webhook_secret` values. They return safe operational metadata such as `has_credentials`, `credential_keys`, and `webhook_secret_configured`.

The package also exposes a gateway-focused REST API immediately after install:

- `POST /api/v1/chat-gateway/webhooks/telegram`
- `POST /api/v1/chat-gateway/webhooks/whatsapp`
- `POST /api/v1/chat-gateway/webhooks/viber`
- `POST /api/v1/chat-gateway/messages`
- `GET /api/v1/chat-gateway/messages/{message}`
- `POST /api/v1/chat-gateway/messages/{message}/retry`
- `GET /api/v1/chat-gateway/conversations`
- `GET /api/v1/chat-gateway/conversations/{conversation}`
- `GET /api/v1/chat-gateway/conversations/{conversation}/messages`
- `GET /api/v1/chat-gateway/channels`
- `POST /api/v1/chat-gateway/channels`
- `GET /api/v1/chat-gateway/channels/{channel}`
- `PATCH /api/v1/chat-gateway/channels/{channel}`

Protected API routes use `chat-gateway.api.middleware.protected` so host applications can attach auth middleware without modifying package routes.

Unsupported providers return a clear JSON client error:

```json
{
    "success": false,
    "message": "Unsupported provider [foo]."
}
```

Use the sandbox only when you want a ready-made local host app for package development, migration checks, webhook verification, or polling validation.

Start with [docs/01-getting-started/local-sandbox.md](docs/01-getting-started/local-sandbox.md).

## Package boundaries

This package does not include AI orchestration, prompt management, RAG, CRM workflow, campaigns, inbox assignment, analytics UI, or automatic attachment downloads.

## Documentation

Start with [docs/README.md](docs/README.md).

## Development

```bash
composer lint:all
composer test
```

## AI guidance

See [AGENTS.md](AGENTS.md) and [.agents/skills/chat-gateway-foundation/SKILL.md](.agents/skills/chat-gateway-foundation/SKILL.md).
