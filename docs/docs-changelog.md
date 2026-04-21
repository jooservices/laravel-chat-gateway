# Docs Changelog

## 2026-04-21

- created the initial documentation tree for `jooservices/laravel-chat-gateway`
- documented architecture, storage, event policy, testing policy, provider extension flow, and examples
- added AI-facing repository guidance and skill instructions
- added a committed local sandbox guide for `_sandbox/laravel-app`
- documented Telegram-first sandbox workflow, webhook setup, and local-dev polling helper usage
- documented poll as the default inbound mode and callback as the optional channel mode
- documented `gateway:poll {provider}` and polling state persistence
- clarified that the package is usable directly in any Laravel app and that `_sandbox/` remains local-only for integration testing
- added Copilot ignore rules so `_sandbox/` and generated dependencies stay out of AI context by default
- documented shared Redis plus Horizon outbound queue support, named package queues, and the synchronous inbound boundaries
- documented the `/api/v1/chat-gateway` REST API surface for webhooks, messages, conversations, and channels
- documented the protected API middleware config, shared `DispatchChatMessageJob`, PATCH-based channel state updates, and unsupported-provider API errors