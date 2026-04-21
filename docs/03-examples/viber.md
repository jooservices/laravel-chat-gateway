# Viber Example

Channel credentials:

```json
{
  "auth_token": "your-viber-auth-token"
}
```

Webhook verification uses `X-Viber-Content-Signature` as an HMAC signature over the raw body with `webhook_secret`.

Outbound sends use `X-Viber-Auth-Token` and the `/pa/send_message` API.