# First Provider Setup

Create a `chat_channels` record with:

- `provider`
- `channel_key`
- `status = active`
- `is_default` when it should be the default for that provider
- `credentials` JSON for provider tokens or ids
- `settings` JSON for provider-specific options
- `webhook_secret`

Recommended `settings` examples:

- poll default: `{ "inbound_mode": "poll", "polling": { "enabled": true } }`
- callback mode: `{ "inbound_mode": "callback", "webhook": { "enabled": true } }`

Example channel credential patterns:

- Telegram: `bot_token`
- Viber: `auth_token`
- WhatsApp: `access_token`, `phone_number_id`, optional `app_secret`

Register the webhook URL with the provider using:

`/chat-gateway/webhooks/{provider}/{channelKey?}`

Use the verify route when the provider requires a challenge:

`/chat-gateway/webhooks/{provider}/{channelKey?}/verify`

For local Telegram-first testing in this repository, the sandbox app seeds a `telegram-default` channel under `_sandbox/laravel-app` and exposes helper endpoints plus local-dev artisan commands. See [Local Sandbox](local-sandbox.md) and [Telegram](../03-examples/telegram.md).

For the default poll flow, run:

`php artisan gateway:poll telegram --channel=telegram-default`