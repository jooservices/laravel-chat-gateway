# Contacts

`ChatContact` stores the external identity boundary for a user:

- provider-specific contact id
- username when available
- display name when available
- phone number when available
- avatar URL when available

Contacts are upserted during inbound webhook processing. The package treats the provider external id as the stable integration key.