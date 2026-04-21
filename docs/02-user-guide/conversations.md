# Conversations

`ChatConversation` links a contact to a channel and an external chat thread.

The package creates or updates conversations during inbound processing and uses them as the preferred outbound resolution context.

Conversation runtime events:

- `ConversationCreated`
- `ConversationUpdated`
- `ConversationClosed`

`ConversationUpdated` stays runtime-only by default. It is not persisted into sourcing by default.