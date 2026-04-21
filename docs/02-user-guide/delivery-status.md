# Delivery Status

Status webhooks update the existing `ChatMessage` when the provider supplies a stable external message id.

Current status event handling:

- Viber: delivered and seen
- WhatsApp: delivered and read
- Telegram: no delivery or read receipt support in this version

Status updates dispatch `MessageStatusUpdated` and are bridged into audit plus sourcing according to the event policy.