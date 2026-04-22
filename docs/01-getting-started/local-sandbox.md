# Local Sandbox

This repository contains a local-only Laravel 12 sandbox application under `_sandbox/laravel-app`.

You do not need the sandbox to use `jooservices/laravel-chat-gateway`. Install the package into any Laravel application when you want normal package usage. Use the sandbox only when you want an in-repo integration host for local testing of package changes.

Rules:

- the sandbox stays inside this package repository
- `_sandbox/` is git-ignored and must not be committed
- local secrets belong only in `_sandbox/laravel-app/.env`
- use local PHP, Composer, Artisan, MySQL or MariaDB, MongoDB, and Redis only

## Purpose

The sandbox installs `jooservices/laravel-chat-gateway` from the local filesystem through a Composer path repository so you can test:

- package installation
- migrations
- package webhook routes
- package poll mode
- Telegram outbound sending
- Telegram inbound webhook handling
- package configuration
- audit and sourcing integration through `jooservices/laravel-events`
- local `php artisan serve` workflow

## Folder layout

```text
_sandbox/
  laravel-app/
    app/
    bootstrap/
    config/
    database/
    routes/
    .env
    composer.json
```

## Composer path repository

The sandbox app uses this Composer repository entry:

```json
{
  "type": "path",
  "url": "../../",
  "options": {
    "symlink": true
  }
}
```

The sandbox requires:

```json
"jooservices/laravel-chat-gateway": "*@dev"
```

This keeps local package changes reflected immediately in the sandbox.

## Local infrastructure

The sandbox is configured for:

- MySQL or MariaDB at `127.0.0.1:3306`
- Redis at `127.0.0.1:6379`
- MongoDB at `127.0.0.1:27017`

The operational database name is:

```text
laravel-chat-gateway
```

The local MongoDB database used for audit and sourcing is:

```text
laravel-chat-gateway-events
```

## Sandbox install commands

From the package root:

```bash
cd _sandbox/laravel-app
composer install
php artisan key:generate
php artisan migrate --seed --force
php artisan events:install-indexes
php artisan serve --host=127.0.0.1 --port=8000
```

The package migrations are auto-loaded by the package service provider. A plain `php artisan migrate` in the sandbox must create the `chat_*` operational tables in MySQL before polling or webhook tests are expected to persist anything.

If the MySQL database does not exist yet:

```bash
mysql -h127.0.0.1 -P3306 -uroot -proot -e 'CREATE DATABASE IF NOT EXISTS `laravel-chat-gateway` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
```

## Sandbox env structure

These keys are configured in `_sandbox/laravel-app/.env`:

```dotenv
APP_NAME="Laravel Chat Gateway Sandbox"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel-chat-gateway
DB_USERNAME=root
DB_PASSWORD=root

MONGODB_HOST=127.0.0.1
MONGODB_PORT=27017
MONGODB_DATABASE=laravel-chat-gateway-events
MONGODB_AUTH_DATABASE=admin

EVENTS_CONNECTION=mongodb
EVENTS_EVENTSOURCING_ENABLED=true
EVENTS_EVENT_LOG_ENABLED=true

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CHAT_GATEWAY_DEFAULT_PROVIDER=telegram
CHAT_GATEWAY_ROUTE_PREFIX=chat-gateway
CHAT_GATEWAY_CACHE_STORE=redis
CHAT_GATEWAY_QUEUE_CONNECTION=redis
CHAT_GATEWAY_TELEGRAM_CHANNEL_KEY=telegram-default
CHAT_GATEWAY_TELEGRAM_CHANNEL_NAME="Sandbox Telegram"

TELEGRAM_BOT_TOKEN=...
TELEGRAM_CHAT_ID=...
TELEGRAM_WEBHOOK_SECRET_TOKEN=...
```

## Seeded Telegram channel

`php artisan migrate --seed --force` creates or updates a seeded `chat_channels` row with:

- `provider = telegram`
- `channel_key = telegram-default`
- `status = active`
- `is_default = true`
- `credentials.bot_token` from `TELEGRAM_BOT_TOKEN`
- `settings.default_chat_id` from `TELEGRAM_CHAT_ID`
- `webhook_secret` from `TELEGRAM_WEBHOOK_SECRET_TOKEN`

## Sandbox HTTP endpoints

Sandbox-specific routes:

- `GET /api/sandbox/health`
- `GET /api/sandbox/state`
- `POST /api/sandbox/telegram/send`

Package routes exposed in the sandbox:

- `POST /api/v1/chat-gateway/webhooks/telegram`
- `POST /api/v1/chat-gateway/webhooks/whatsapp`
- `POST /api/v1/chat-gateway/webhooks/viber`
- `POST /api/v1/chat-gateway/messages`
- `GET /api/v1/chat-gateway/messages/{message}`
- `POST /api/v1/chat-gateway/messages/{message}/retry`
- `PATCH /api/v1/chat-gateway/channels/{channel}`

Package poll command exposed in the sandbox:

- `php artisan gateway:poll telegram`
- `php artisan gateway:poll telegram --once`
- `php artisan gateway:poll telegram --channel=telegram-default --timeout=30 --limit=100`

Operational expectations in the sandbox:

- `chat_channels`, `chat_contacts`, `chat_conversations`, `chat_messages`, `chat_attachments`, `chat_webhook_events`, `chat_message_status_logs`, and `chat_polling_states` persist in MySQL
- audit and sourcing remain separate in MongoDB through `jooservices/laravel-events`
- polling state offset advances only after successful update processing

Examples:

```bash
curl http://127.0.0.1:8000/api/sandbox/health
curl http://127.0.0.1:8000/api/sandbox/state
curl -X POST http://127.0.0.1:8000/api/sandbox/telegram/send \
  -H 'Content-Type: application/json' \
  -d '{"content":"Hello from the sandbox"}'
```

Reply using a stored conversation:

```bash
curl -X POST http://127.0.0.1:8000/api/sandbox/telegram/send \
  -H 'Content-Type: application/json' \
  -d '{"conversation_id":1,"content":"Reply using stored conversation context"}'
```

## Telegram webhook setup

The sandbox Telegram webhook path is:

```text
/api/v1/chat-gateway/webhooks/telegram
```

Telegram webhook reality for local development:

- poll is the default inbound mode in the package now
- outbound `sendMessage` works immediately with a valid bot token and `chat_id`
- inbound webhook delivery requires a public HTTPS URL
- Telegram webhook mode and `getUpdates` polling should not be mixed carelessly
- if `secret_token` is set on `setWebhook`, Telegram sends `X-Telegram-Bot-Api-Secret-Token`
- idle long-poll batches are expected and must not be treated as command failures
- the HTTP client timeout must remain larger than the Telegram `getUpdates` timeout

Polling operations note:

- use `php artisan gateway:poll telegram --once` for intentional one-shot polling
- use a Supervisor-managed long-running process for continuous poll mode
- cron or scheduler execution is only appropriate when you intentionally want discrete one-shot poll runs

The sandbox includes helper commands:

- `php artisan gateway:poll telegram --once`
- `php artisan sandbox:telegram:webhook-info`
- `php artisan sandbox:telegram:set-webhook {publicBaseUrl}`
- `php artisan sandbox:telegram:delete-webhook`
- `php artisan sandbox:telegram:poll-updates`

Set the webhook once you have a public HTTPS tunnel:

```bash
php artisan sandbox:telegram:set-webhook https://your-public-host.example
```

That registers:

```text
https://your-public-host.example/api/v1/chat-gateway/webhooks/telegram
```

with `secret_token = TELEGRAM_WEBHOOK_SECRET_TOKEN`.

## Local-dev polling helper

`sandbox:telegram:poll-updates` exists only for local development.

- it is not the primary production mode
- prefer the package-native `gateway:poll telegram` command for normal poll-mode testing
- it is useful when the sandbox is running on `php artisan serve` without a public tunnel
- it should generally be used only after deleting the active Telegram webhook
- it replays Telegram updates into the package webhook service with the configured secret token

## Troubleshooting

- If outbound send fails before the HTTP call, confirm the seeded Telegram channel exists with `GET /api/sandbox/state`.
- If webhook verification fails, confirm `TELEGRAM_WEBHOOK_SECRET_TOKEN` matches the value used in `setWebhook` and the incoming `X-Telegram-Bot-Api-Secret-Token` header.
- If audit or sourcing writes fail, confirm MongoDB is reachable and rerun `php artisan events:install-indexes`.
- If cache or dedupe behaves unexpectedly, confirm Redis is reachable and `CHAT_GATEWAY_CACHE_STORE=redis`.
- If MySQL migrations fail, create the `laravel-chat-gateway` database explicitly before running `php artisan migrate --seed --force`.
