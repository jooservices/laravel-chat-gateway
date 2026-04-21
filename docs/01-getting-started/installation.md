# Installation

Install the package:

```bash
composer require jooservices/laravel-chat-gateway
```

This package is designed to run inside your own Laravel application. The `_sandbox/laravel-app` folder in this repository is optional and exists only as a local integration host for package development and verification.

Laravel package discovery registers the service provider automatically, so package config and migrations are loaded without any manual provider registration.

Publish config and migrations only if you need local copies:

```bash
php artisan vendor:publish --tag=chat-gateway-config
php artisan vendor:publish --tag=chat-gateway-migrations
```

Run migrations:

```bash
php artisan migrate
```

Operational tables are created in your Laravel relational database connection and are required for channels, contacts, conversations, messages, attachments, webhook events, message status logs, and polling state. If you need the package to use a non-default relational connection, set `CHAT_GATEWAY_DB_CONNECTION`.

Ensure you also have:

- MySQL or MariaDB configured for operational persistence
- Redis configured for cache and dedupe
- MongoDB configured for `jooservices/laravel-events`

For a ready-to-run local Laravel 12 host app that installs this package through a Composer path repository, use the optional sandbox workflow documented in [Local Sandbox](local-sandbox.md).