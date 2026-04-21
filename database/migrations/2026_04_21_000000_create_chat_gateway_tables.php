<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_channels', static function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32);
            $table->string('channel_key', 191);
            $table->string('name', 191);
            $table->string('status', 32);
            $table->boolean('is_default')->default(false);
            $table->json('credentials');
            $table->json('settings');
            $table->string('webhook_secret', 191);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'channel_key']);
            $table->index(['provider', 'status']);
            $table->index('is_default');
        });

        Schema::create('chat_contacts', static function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32);
            $table->foreignId('channel_id')->nullable()->constrained('chat_channels')->nullOnDelete();
            $table->string('external_contact_id', 191);
            $table->string('external_username', 191)->nullable();
            $table->string('display_name', 191)->nullable();
            $table->string('phone_number', 64)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_contact_id', 'channel_id'], 'chat_contacts_provider_external_channel_unique');
            $table->index('display_name');
            $table->index('phone_number');
        });

        Schema::create('chat_conversations', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('channel_id')->constrained('chat_channels')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('chat_contacts')->cascadeOnDelete();
            $table->string('external_chat_id', 191);
            $table->string('status', 32);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'external_chat_id']);
            $table->index(['contact_id', 'status']);
            $table->index('last_message_at');
        });

        Schema::create('chat_messages', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('direction', 32);
            $table->string('type', 32);
            $table->string('status', 32);
            $table->string('external_message_id', 191)->nullable();
            $table->string('reply_to_message_id', 191)->nullable();
            $table->text('content')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('raw_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_message_id']);
            $table->index(['conversation_id', 'created_at']);
            $table->index('status');
            $table->index('direction');
        });

        Schema::create('chat_attachments', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('external_file_id', 191)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('mime_type', 191)->nullable();
            $table->string('file_name', 191)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('message_id');
            $table->index('external_file_id');
        });

        Schema::create('chat_webhook_events', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('channel_id')->nullable()->constrained('chat_channels')->nullOnDelete();
            $table->string('provider', 32);
            $table->string('external_event_id', 191)->nullable();
            $table->string('event_type', 64)->nullable();
            $table->string('status', 32);
            $table->string('payload_hash', 64)->nullable();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->text('reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_event_id']);
            $table->index(['provider', 'status']);
            $table->index('channel_id');
            $table->index('processed_at');
            $table->index('payload_hash');
        });

        Schema::create('chat_message_status_logs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->string('old_status', 32)->nullable();
            $table->string('new_status', 32);
            $table->string('provider_status', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('message_id');
            $table->index('new_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_status_logs');
        Schema::dropIfExists('chat_webhook_events');
        Schema::dropIfExists('chat_attachments');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('chat_contacts');
        Schema::dropIfExists('chat_channels');
    }
};
