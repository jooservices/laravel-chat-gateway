<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', static function (Blueprint $table): void {
            $table->string('chat_type', 32)->nullable()->after('external_chat_id');
            $table->string('chat_title', 191)->nullable()->after('chat_type');
            $table->string('chat_username', 191)->nullable()->after('chat_title');
            $table->index('chat_type');
        });

        Schema::table('chat_webhook_events', static function (Blueprint $table): void {
            $table->dropUnique(['provider', 'external_event_id']);
            $table->unique(['provider', 'channel_id', 'external_event_id'], 'chat_webhook_events_provider_channel_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chat_webhook_events', static function (Blueprint $table): void {
            $table->dropUnique('chat_webhook_events_provider_channel_event_unique');
            $table->unique(['provider', 'external_event_id']);
        });

        Schema::table('chat_conversations', static function (Blueprint $table): void {
            $table->dropIndex(['chat_type']);
            $table->dropColumn(['chat_type', 'chat_title', 'chat_username']);
        });
    }
};
