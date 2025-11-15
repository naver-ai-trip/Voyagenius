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
        if (Schema::hasTable('chat_messages')) {
            return;
        }

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->onDelete('cascade');
            $table->string('from_role'); // user, assistant, system
            $table->string('message_type')->default('text'); // text, suggestion, action_result, error
            $table->text('content');
            $table->json('metadata')->nullable(); // Function calls, parameters, results
            $table->json('references')->nullable(); // Links to entities (places, itineraries, etc.)
            $table->timestamps();

            // Indexes for performance
            $table->index('chat_session_id');
            $table->index('from_role');
            $table->index('message_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
