# Outbound Message Flow

Outbound flow is intentionally narrow:

1. resolve channel and provider
2. create outbound message record
3. send via provider sender
4. normalize provider response
5. update status and timestamps
6. create message status log
7. dispatch runtime events
8. bridge audit and sourcing records where required

Outbound channel resolution order:

1. `conversation_id` -> channel
2. `channel_id`
3. `channel_key`

The package does not blindly fall back to a default provider when existing message context already points to another channel.