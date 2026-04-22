# Docs Changelog

## 2026-04-22

- corrected webhook examples to the fixed `/api/v1/chat-gateway/webhooks/{provider}` API routes
- documented safe channel API response metadata instead of raw credential or webhook-secret output
- clarified that outbound API sends respect `chat-gateway.queue.enabled` and use the shared outbound job only when queueing is enabled
- documented that `POST /api/v1/chat-gateway/messages` accepts either `conversation_id` or `external_chat_id`

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
