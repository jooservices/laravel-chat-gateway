<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_webhook_events', static function (Blueprint $table): void {
            $table->string('transport', 32)->default('callback')->after('provider');
            $table->index(['provider', 'transport'], 'chat_webhook_events_provider_transport_index');
        });

        Schema::create('chat_polling_states', static function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32);
            $table->foreignId('channel_id')->nullable()->constrained('chat_channels')->nullOnDelete();
            $table->unsignedBigInteger('offset')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'channel_id'], 'chat_polling_states_provider_channel_unique');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_polling_states');

        Schema::table('chat_webhook_events', static function (Blueprint $table): void {
            $table->dropIndex('chat_webhook_events_provider_transport_index');
            $table->dropColumn('transport');
        });
    }
};