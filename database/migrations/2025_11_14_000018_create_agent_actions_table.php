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
        if (Schema::hasTable('agent_actions')) {
            return;
        }

        Schema::create('agent_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('chat_message_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action_type'); // create_itinerary, search_place, add_checkpoint, etc.
            $table->string('entity_type')->nullable(); // Trip, Place, ItineraryItem, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('action_data');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('chat_session_id');
            $table->index('chat_message_id');
            $table->index('action_type');
            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_actions');
    }
};
