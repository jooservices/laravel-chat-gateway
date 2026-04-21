# Package Overview

`jooservices/laravel-chat-gateway` is a Laravel package for multi-provider chat transport. It receives inbound webhooks, verifies and deduplicates them, normalizes payloads into DTOs, persists operational state, sends outbound messages, dispatches runtime events, and bridges audit plus event-sourcing records through `jooservices/laravel-events`.

It is intended to be installed into a Laravel application as a normal package. The `_sandbox/laravel-app` folder that exists in this repository is only a local integration host for package development and is not part of the package runtime contract.

The package is intentionally limited to the transport and persistence boundary. It is a foundation for future AI chat systems, but it does not perform AI orchestration itself.