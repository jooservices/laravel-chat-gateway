# Class Relationships

Primary orchestration chain:

- request -> `WebhookController`
- `WebhookController` -> `WebhookRequest`
- `WebhookRequest` -> `WebhookService`
- `WebhookService` -> `ChannelService`, repositories, provider parser and verifier, `ConversationService`, `MessageService`
- `MessageService` -> provider sender through `ChatGatewayManager`
- runtime events -> subscribers -> `AuditEventBridge`
- `AuditEventBridge` -> `JooServices\LaravelEvents\EventService`

Persistence chain:

- services -> repository contracts -> repository classes -> models

Provider chain:

- service -> manager -> provider -> parser or verifier or sender -> HTTP client factory