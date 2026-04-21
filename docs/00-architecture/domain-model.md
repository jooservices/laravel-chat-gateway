# Domain Model

Operational entities:

- `ChatChannel`: provider account or bot configuration per runtime channel
- `ChatContact`: external chat user reference per provider and optional channel
- `ChatConversation`: contact-thread context inside a specific channel
- `ChatMessage`: inbound or outbound message record
- `ChatAttachment`: attachment metadata only
- `ChatWebhookEvent`: raw operational webhook receipt and processing state
- `ChatMessageStatusLog`: chronological status transitions for a message

DTO boundary objects:

- `InboundWebhookDto`: normalized inbound provider event
- `OutboundMessageDto`: outbound message command payload
- `OutboundMessageResultDto`: normalized provider send result
- `ProviderCapabilitiesDto`: provider capability matrix
- `VerificationResultDto`: webhook verification result

This split keeps transport normalization out of Eloquent models and business orchestration out of controllers.