# First Provider Setup

Create a `chat_channels` record with:

- `provider`
- `channel_key`
- `status = active`
- `is_default` when it should be the default for that provider
- `credentials` JSON for provider tokens or ids
- `settings` JSON for provider-specific options
- `webhook_secret`

You can persist the row directly or through `ProviderChannelService`, which validates provider-specific credentials and settings before writing the channel record.

Recommended `settings` examples:

- poll default: `{ "inbound_mode": "poll", "polling": { "enabled": true } }`
- callback mode: `{ "inbound_mode": "callback", "webhook": { "enabled": true } }`

Example channel credential patterns:

- Telegram: `bot_token`
- Viber: `auth_token` or `access_token`
- WhatsApp: `access_token`, `phone_number_id`, optional `app_secret`

Webhook secret rules:

- Telegram callback verification requires a non-empty `webhook_secret`
- Viber callback verification requires a non-empty `webhook_secret`
- WhatsApp challenge or signature verification requires a non-empty channel secret source

Register the webhook URL with the provider using:

`/chat-gateway/webhooks/{provider}/{channelKey?}`

Use the verify route when the provider requires a challenge:

`/chat-gateway/webhooks/{provider}/{channelKey?}/verify`

For local Telegram-first testing in this repository, the sandbox app seeds a `telegram-default` channel under `_sandbox/laravel-app` and exposes helper endpoints plus local-dev artisan commands. See [Local Sandbox](local-sandbox.md) and [Telegram](../03-examples/telegram.md).

For the default poll flow, run:

`php artisan gateway:poll telegram --channel=telegram-default`