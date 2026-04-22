# Channels

`ChatChannel` is the runtime provider-account record.

Use channels to model:

- multiple Telegram bots
- multiple Viber public accounts
- multiple WhatsApp business numbers

Provider is not the same as channel. One provider can have many channels.

The package now exposes `ProviderChannelService` for channel lifecycle writes:

- register a provider channel with validated runtime credentials
- update channel credentials, settings, webhook secret, and metadata
- activate or deactivate a channel through status changes
- mark one channel as the provider default

REST API endpoints:

- `GET /api/v1/chat-gateway/channels`
- `POST /api/v1/chat-gateway/channels`
- `GET /api/v1/chat-gateway/channels/{channel}`
- `PATCH /api/v1/chat-gateway/channels/{channel}`

Channel state changes are payload-based on `PATCH /channels/{channel}`:

- `status=active|inactive`
- `is_default=true|false`

The API does not expose separate activate, deactivate, or default-channel endpoints.

Channel API responses do not expose raw provider credentials or webhook secrets. `GET /channels` and `GET /channels/{channel}` return safe metadata instead:

- `has_credentials`
- `credential_keys`
- `webhook_secret_configured`

Inbound channel resolution order:

1. route `channelKey`
2. provider-specific payload or header mapping when available
3. provider default channel
4. reject when unresolved
