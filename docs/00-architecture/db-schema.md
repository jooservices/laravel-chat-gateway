# DB Schema

Operational tables:

- `chat_channels`
- `chat_contacts`
- `chat_conversations`
- `chat_messages`
- `chat_attachments`
- `chat_webhook_events`
- `chat_message_status_logs`
- `chat_polling_states`

Operational database rules:

- use MySQL or MariaDB for these tables
- keep attachment storage metadata-only in this version
- keep raw webhook and raw message payload persistence conscious and configurable

Conversation origin fields live on `chat_conversations`:

- `chat_type`
- `chat_title`
- `chat_username`

Webhook dedupe is channel-scoped. External event id uniqueness for operational webhook rows is enforced per provider and channel, not per provider alone.

MongoDB persistence is not duplicated here. Audit and sourcing records live in `event_logs` and `stored_events` through `jooservices/laravel-events`.