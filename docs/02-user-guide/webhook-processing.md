# Webhook Processing

Webhook processing is synchronous by default and writes operational records immediately.

Inbound ingestion runs inside a database transaction on the package model connection so contact, conversation, message, status-log, and close-state writes either commit together or roll back together.

Normalized event taxonomy distinguishes at least:

- `message`
- `delivery_status`
- `read_status`
- `callback_action`
- `membership`
- `system`

The normalized inbound DTO also carries:

- `message_type`
- `interaction_type`
- `is_message_event`
- `is_status_event`
- `is_interaction_event`

Webhook verification rejects channels with an empty configured secret instead of accepting an empty incoming secret.