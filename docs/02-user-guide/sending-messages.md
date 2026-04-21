# Sending Messages

Use `MessageService` or `ChatGateway` to send outbound messages.

Recommended outbound inputs:

- `conversationId` when replying in an existing thread
- `channelId` when channel is known explicitly
- `channelKey` only when no conversation exists yet
- `externalChatId` when the provider recipient id must be explicit

Provider capability checks run before the HTTP send operation.

Only the network boundary is mocked in tests. Outbound message persistence and status updates use the real database in package tests.