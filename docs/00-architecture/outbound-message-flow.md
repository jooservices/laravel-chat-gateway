# Outbound Message Flow

Outbound flow is intentionally narrow:

1. resolve channel and provider
2. create outbound message record
3. if queueing is enabled, dispatch one shared outbound job to `chat-outbound` after commit
4. otherwise send inline via provider sender
5. normalize provider response
6. update status and timestamps
7. create message status log
8. dispatch runtime events
9. bridge audit and sourcing records where required

Outbound channel resolution order:

1. `conversation_id` -> channel
2. `channel_id`
3. `channel_key`

The package does not blindly fall back to a default provider when existing message context already points to another channel.

The shared outbound job stays provider-agnostic. Provider-specific logic remains inside the existing provider sender classes and is invoked by `MessageService` during queued execution.