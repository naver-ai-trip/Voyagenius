<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('agent_webhooks')) {
            return;
        }

        Schema::create('agent_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('url'); // Webhook endpoint URL
            $table->json('events'); // Array of events to subscribe to
            $table->string('secret', 64); // Secret key for signature verification
            $table->boolean('is_active')->default(true);
            $table->integer('retry_count')->default(3);
            $table->integer('timeout_seconds')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_webhooks');
    }
};
