# Folder Structure

The package layout is organized around clear ownership:

- `src/Contracts`: provider, repository, and service contracts
- `src/DTOs`: normalized transport objects built on `jooservices/dto`
- `src/Enums`: package enums for providers, statuses, and event types
- `src/Events`: runtime Laravel events
- `src/Http`: webhook controller, request, and resource
- `src/Models`: operational Eloquent models
- `src/Providers`: Telegram, Viber, and WhatsApp modules
- `src/Repositories`: persistence and lookup layer
- `src/Services`: orchestration, channel resolution, outbound sending, webhook flow, and audit bridge
- `src/Subscribers`: runtime event subscribers
- `database/migrations`: operational schema
- `tests`: PHPUnit feature and unit tests
- `docs`: human-facing package documentation

There is no general-purpose `Support` bucket. Utility code exists only where justified.