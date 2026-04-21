# WhatsApp Example

Channel credentials:

```json
{
  "access_token": "EAAB...",
  "phone_number_id": "1234567890",
  "app_secret": "meta-app-secret"
}
```

Verification rules:

- GET challenge compares `hub_verify_token` with `webhook_secret`
- POST signature verifies `X-Hub-Signature-256`

Outbound sends post to the Graph API version configured under `providers.whatsapp.api_version`.