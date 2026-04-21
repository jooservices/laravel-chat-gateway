# Provider Architecture

Each provider lives in its own module:

- `Provider`: top-level provider adapter implementing `ChatProviderContract`
- `WebhookParser`: inbound normalization
- `WebhookVerifier`: signature or challenge verification
- `MessageSender`: outbound send execution
- `Mapper`: payload mapping helpers for inbound and outbound shapes

Provider selection is channel-driven, not random business-layer branching. `ChatGatewayManager` resolves the provider from the channel and exposes the provider capabilities.

Capabilities tracked per provider:

- text
- image or file
- button or interaction
- delivery receipt
- read receipt