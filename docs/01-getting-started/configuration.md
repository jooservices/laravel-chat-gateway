# Configuration

The package config lives in `config/chat-gateway.php`.

Important groups:

- `default_provider`
- `inbound`
- `database`
- `routes`
- `webhooks`
- `messages`
- `conversations`
- `queue`
- `cache`
- `events`
- `providers`
- `logging`

Runtime credentials belong primarily in `chat_channels.credentials` and `chat_channels.settings`, not only in config.

Provider channel writes should validate provider-specific credentials and settings before persistence. The package now exposes a `ProviderChannelService` for register, update, activate, deactivate, and default-channel operations while keeping runtime credentials DB-backed in `chat_channels`.

Inbound mode defaults:

- `chat-gateway.inbound.default_mode = poll`
- each provider config may override `providers.{provider}.inbound_mode`
- each `chat_channels.settings.inbound_mode` may override the provider default per channel

Telegram provider config now supports both inbound transports:

- `providers.telegram.inbound_mode = poll|callback`
- `providers.telegram.polling.enabled`
- `providers.telegram.polling.timeout`
- `providers.telegram.polling.limit`
- `providers.telegram.polling.allowed_updates`
- `providers.telegram.webhook.enabled`
- `providers.telegram.webhook.secret_token`

Recommended operational model:

- local development and homelab: use `poll`
- public production ingress: use `callback`
- do not mix polling and webhook delivery carelessly for the same Telegram bot or channel

Polling state is persisted in `chat_polling_states`, not cache only.

Use the package command:

```bash
php artisan gateway:poll telegram
php artisan gateway:poll telegram --once
php artisan gateway:poll telegram --channel=telegram-default --timeout=30 --limit=100
```

Cache key strategy:

- `chat_gateway:channel:{provider}:{channel_key}`
- `chat_gateway:conversation:{channel_id}:{external_chat_id}`
- `chat_gateway:webhook_dedupe:channel:{channel_id}:{provider}:{external_event_id}`
- `chat_gateway:poll_dedupe:channel:{channel_id}:{provider}:{external_event_id}`

Privacy defaults:

- redact tokens and secrets from logs
- optionally redact phone numbers
- avoid storing raw payloads unless operationally necessary