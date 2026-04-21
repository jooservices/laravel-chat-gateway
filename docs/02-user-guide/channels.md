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

Inbound channel resolution order:

1. route `channelKey`
2. provider-specific payload or header mapping when available
3. provider default channel
4. reject when unresolved