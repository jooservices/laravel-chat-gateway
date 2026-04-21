# Risks / Gaps

Deliberate non-scope items:

- AI orchestration and prompt workflows
- CRM or human-support workflow
- campaign and broadcast features
- reporting UI
- attachment binary download and storage jobs by default

Current implementation notes:

- Telegram has no delivery or read receipt support in this version
- outbound channel resolution by `channelKey` alone uses the configured default provider because the DTO does not carry an explicit provider field
- audit and sourcing integration is implemented through direct `EventService` bridge calls rather than event classes implementing `jooservices/laravel-events` interfaces directly