# Sending Messages

Use `MessageService` or `ChatGateway` to send outbound messages.

Recommended outbound inputs:

- `conversationId` when replying in an existing thread
- `channelId` when channel is known explicitly
- `channelKey` only when no conversation exists yet
- `externalChatId` when the provider recipient id must be explicit

Provider capability checks run before the HTTP send operation.

Outbound sending can be queued through Redis with Horizon supervising the shared `chat-outbound` queue. The package uses one shared outbound job for all providers, and the real send logic remains in `MessageService` plus the provider sender classes.

The following inbound steps stay synchronous even when queueing is enabled:

- webhook verification
- inbound dedupe
- polling fetch loops
- minimal inbound persistence for correctness
- polling cursor updates

Only the network boundary is mocked in tests. Outbound message persistence and status updates use the real database in package tests.