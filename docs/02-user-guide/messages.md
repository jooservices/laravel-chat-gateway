# Messages

`ChatMessage` stores inbound and outbound message state.

Supported statuses:

- `pending`
- `queued`
- `sent`
- `delivered`
- `read`
- `failed`

When queueing is enabled, outbound messages are first stored as `queued` and later transitioned by the shared outbound job running on `chat-outbound`.

`ChatMessageStatusLog` captures each transition with the optional provider status payload.

REST API endpoints:

- `POST /api/v1/chat-gateway/messages`
- `GET /api/v1/chat-gateway/messages/{message}`
- `POST /api/v1/chat-gateway/messages/{message}/retry`

`POST /messages` creates the outbound message resource and queues the shared outbound job. There is no separate `/messages/send` endpoint.

`GET /messages/{message}` includes the current message `status` directly in the response payload together with the latest `provider_status` when one has been recorded. There is no separate `/messages/{id}/status` endpoint.

If the request references an unsupported provider, the API returns a clear client error response:

```json
{
	"success": false,
	"message": "Unsupported provider [foo]."
}
```

Attachment handling in this version stores metadata only:

- external file id
- URL
- MIME type
- file name
- file size
- metadata