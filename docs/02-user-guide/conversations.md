# Conversations

`ChatConversation` links a contact to a channel and an external chat thread.

The package creates or updates conversations during inbound processing and uses them as the preferred outbound resolution context.

When the current provider payload includes chat-origin metadata, the package persists it on the conversation record:

- `external_chat_id`
- `chat_type`
- `chat_title`
- `chat_username`

Telegram currently populates these fields from the inbound `chat` object. Other providers leave them null unless the current payload and mapping flow expose equivalent values.

`started_at` is set only when the conversation is first created. Later updates refresh `last_message_at` without overwriting the original conversation start timestamp.

Conversation runtime events:

- `ConversationCreated`
- `ConversationUpdated`
- `ConversationClosed`

`ConversationUpdated` stays runtime-only by default. It is not persisted into sourcing by default.