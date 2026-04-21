# Inbound Webhook Flow

The package follows this exact flow:

1. receive webhook
2. validate request shape
3. resolve channel
4. verify webhook
5. persist operational webhook event
6. deduplicate with Redis plus database lookup
7. parse normalized event DTO
8. upsert contact
9. find or create conversation
10. create inbound message when applicable
11. create attachment metadata when present
12. mark webhook processed
13. dispatch runtime events
14. bridge audit and sourcing records where policy requires

Preferred dedupe key:

- `provider + external_event_id`

Fallback dedupe key:

- payload hash