# Messages

`ChatMessage` stores inbound and outbound message state.

Supported statuses:

- `pending`
- `queued`
- `sent`
- `delivered`
- `read`
- `failed`

`ChatMessageStatusLog` captures each transition with the optional provider status payload.

Attachment handling in this version stores metadata only:

- external file id
- URL
- MIME type
- file name
- file size
- metadata