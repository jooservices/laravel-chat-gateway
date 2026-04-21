# Webhook Processing

Webhook processing is synchronous by default and writes operational records immediately.

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