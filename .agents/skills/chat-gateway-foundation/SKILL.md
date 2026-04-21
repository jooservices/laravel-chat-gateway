# Chat Gateway Foundation Skill

Use this skill when working in `jooservices/laravel-chat-gateway`.

## What the package does

- receives inbound provider updates through polling and callback or webhook flows
- normalizes inbound payloads into DTOs
- persists channels, contacts, conversations, messages, attachments, webhook events, polling state, and status logs
- sends outbound messages through provider modules
- dispatches runtime events
- bridges audit and sourcing records into `jooservices/laravel-events`

## What the package does not do

- AI orchestration
- prompt or memory management
- CRM and support workflow
- campaign or broadcast logic
- attachment binary download by default

## Official implementation workflow

1. Start at the channel.
2. Resolve the provider through `ChatGatewayManager`.
3. Keep provider logic inside `src/Providers/{Provider}`.
4. Keep orchestration inside services.
5. Keep persistence inside repositories.
6. Persist audit and sourcing through `AuditEventBridge` only.

## Inbound mode policy

- default inbound mode is `poll`
- callback or webhook mode remains supported per channel
- Telegram channels must not mix webhook delivery and `getUpdates` polling carelessly
- polling offsets persist in `chat_polling_states`
- use `php artisan gateway:poll telegram` for default local inbound processing

## Storage architecture

- MySQL or MariaDB: operational tables
- Redis: cache, webhook dedupe, and polling dedupe
- MongoDB: audit and event sourcing through `jooservices/laravel-events`

## Local sandbox workflow

- The repository may include a local-only Laravel 12 host app under `_sandbox/laravel-app`.
- `_sandbox/` is git-ignored and is used for real package integration only.
- Prefer a Composer path repository from `_sandbox/laravel-app` to `../../` with symlinking enabled.
- Use the sandbox to verify package install, migrations, webhook routes, Telegram outbound send, and local inbound simulation.
- Keep any local secrets in `_sandbox/laravel-app/.env`, never in committed package code.

## Event policy

- Runtime events: `src/Events/*`
- Subscribers: `src/Subscribers/*`
- Audit bridge owner: `src/Services/AuditEventBridge.php`
- `ConversationUpdated` is runtime-only by default

## Testing and mocking rule

- Fixture-based mocking only.
- Fixtures live under `tests/Fixtures/`.
- Use `loadFixture()` and `loadJsonFixture()`.
- Mock `ProviderHttpClientFactoryContract`.
- The factory must return mocked `HttpClientInterface` and mocked `ResponseWrapperInterface`.
- Controller tests must hit real routes.
- Service tests must use the real database.

## Provider extension workflow

1. Add a provider folder under `src/Providers/`.
2. Implement provider, parser, verifier, sender, mapper, and polling fetcher classes when the provider supports polling.
3. Register the provider in `LaravelChatGatewayServiceProvider` and `ChatGatewayManager`.
4. Add fixtures for inbound text, inbound media, callback or interactive, outbound success, outbound failed, malformed payload, delivery or read status, and `getUpdates` polling payloads.
5. Add provider tests using the fixture and client-factory mocking pattern.
6. Update docs and this skill file if the public workflow changes.