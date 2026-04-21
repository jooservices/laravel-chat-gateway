# Laravel Chat Gateway Repository Instructions

This repository is the PHP package `jooservices/laravel-chat-gateway`.

## Core boundary

- Keep the package focused on multi-provider chat transport.
- Preserve the request -> controller -> form request -> service -> repository -> model flow.
- Keep provider-specific logic inside `src/Providers/*`.
- Keep audit and event-sourcing persistence delegated to `jooservices/laravel-events`.
- Do not add AI orchestration, inbox workflow, campaigns, analytics UI, or attachment download jobs to the package core.

## Inbound mode policy

- Default inbound mode is poll.
- Callback or webhook mode must remain supported.
- Extend callback-first code safely; do not perform broad renames or a full rewrite just to add polling.
- Prefer a shared downstream ingestion service after provider parsing instead of duplicating contact, conversation, and message persistence logic.
- For Telegram, do not mix webhook delivery and `getUpdates` polling carelessly on the same channel.
- Persist polling cursor state in operational storage, not cache only.

## Storage rules

- Operational state: MySQL or MariaDB.
- Audit and event sourcing: MongoDB through `jooservices/laravel-events`.
- Cache and dedupe: Redis.

## Mocking and tests

- Use fixture-based mocking.
- Store fixtures in `tests/Fixtures/`.
- Use `loadFixture()` and `loadJsonFixture()` from `tests/TestCase.php`.
- Mock only the HTTP network boundary.
- Mock `ProviderHttpClientFactoryContract`.
- Return mocked `JOOservices\Client\Contracts\HttpClientInterface` and mocked `JOOservices\Client\Contracts\ResponseWrapperInterface`.
- Do not introduce provider-specific fake client classes as the primary pattern.

## Event policy

- Runtime events live under `src/Events/`.
- Subscribers route runtime events into the audit and sourcing bridge.
- Audit and sourcing persistence belongs in `AuditEventBridge`.
- Do not replace Laravel's dispatcher with a custom bus.

## Quality rules

- Formatting authority: Pint.
- Static analysis: PHPStan.
- Structural checks: PHPCS.
- Maintainability checks: PHPMD.
- Tests: PHPUnit only.
- Keep docs and tests in the same change when behavior changes.

## Local sandbox

- The repository may contain a local-only Laravel host app under `_sandbox/laravel-app`.
- `_sandbox/` must remain git-ignored and must not be used for committed package code.
- Use the sandbox for real integration checks: path repository install, `php artisan serve`, MySQL or MariaDB, MongoDB, Redis, and Telegram-first flows.
- Keep production package behavior inside `src/`; sandbox-only controllers, routes, env values, and helper commands stay under `_sandbox/laravel-app`.