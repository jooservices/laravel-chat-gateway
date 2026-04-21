# DB Schema

Operational tables:

- `chat_channels`
- `chat_contacts`
- `chat_conversations`
- `chat_messages`
- `chat_attachments`
- `chat_webhook_events`
- `chat_message_status_logs`

Operational database rules:

- use MySQL or MariaDB for these tables
- keep attachment storage metadata-only in this version
- keep raw webhook and raw message payload persistence conscious and configurable

MongoDB persistence is not duplicated here. Audit and sourcing records live in `event_logs` and `stored_events` through `jooservices/laravel-events`.