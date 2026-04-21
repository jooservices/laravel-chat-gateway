# Telegram Example

Inbound mode summary:

- default mode in this package is `poll`
- optional mode is `callback`
- configure per provider and optionally per channel

Channel credentials:

```json
{
  "bot_token": "123456:ABCDEF"
}
```

Webhook verification uses `X-Telegram-Bot-Api-Secret-Token` against `chat_channels.webhook_secret`.

If `chat_channels.webhook_secret` is empty, Telegram webhook verification is rejected.

Typical route:

`POST /chat-gateway/webhooks/telegram/telegram-default`

Typical poll command:

`php artisan gateway:poll telegram --channel=telegram-default --timeout=30 --limit=100`

Typical verify route:

`GET|POST /chat-gateway/webhooks/telegram/telegram-default/verify`

Telegram facts that matter in this package:

- `getUpdates` uses `offset`, `limit`, and `timeout`
- the package persists polling offset in `chat_polling_states`
- polling offset advances only after successful processing of an update
- polled inbound updates persist the same operational MySQL records as callback ingestion: webhook-event records, contacts, conversations, messages, attachments, and status logs where applicable
- idle long-poll responses are expected and should exit cleanly in `--once` mode without being treated as failures
- the HTTP timeout used for Telegram polling must remain greater than the requested `getUpdates` timeout
- polling throws an `InvalidArgumentException` when `credentials.bot_token` is missing for the resolved channel
- outbound `sendMessage` requires a target `chat_id`
- inbound webhook requests include `message.chat.id`, which is normalized into `ConversationContextDto.externalChatId`
- the package persists that value as `chat_conversations.external_chat_id`
- when present, `message.chat.type`, `message.chat.title`, and `message.chat.username` are also persisted on the conversation as chat-origin fields
- replies can use the stored conversation id so the package resolves the same Telegram `chat_id`
- Telegram webhook mode and `getUpdates` polling are mutually exclusive while a webhook is set
- if you set `secret_token` in `setWebhook`, Telegram sends `X-Telegram-Bot-Api-Secret-Token`

Typical outbound message payload:

```php
new OutboundMessageDto(
    conversationId: 10,
    channelId: null,
    channelKey: null,
    externalChatId: null,
    type: 'text',
    content: 'Hello Telegram'
);
```

  If you do not already have a conversation id, the package can bootstrap an outbound conversation from `channelKey` plus `externalChatId` and persist that chat context for later replies.

  Callback mode remains available for public HTTPS deployments. For a callback-driven Telegram channel, set `settings.inbound_mode = callback` and enable webhook delivery for that channel.

  For continuous poll mode in production, run the command under Supervisor or another long-lived process manager. Scheduler or cron usage is suitable only for explicit one-shot polling strategies.

  For the local sandbox workflow in this repository, see [Local Sandbox](../01-getting-started/local-sandbox.md).