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
        if (Schema::hasTable('trip_recommendations')) {
            return;
        }

        Schema::create('trip_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('chat_session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('recommendation_type'); // place, activity, route, accommodation, restaurant
            $table->string('source')->default('ai_agent'); // ai_agent, naver_api, user_suggestion
            $table->json('data'); // Complete recommendation data
            $table->decimal('confidence_score', 5, 2)->nullable(); // 0.00 to 100.00
            $table->string('status')->default('pending'); // pending, accepted, rejected, modified
            $table->timestamps();

            // Indexes for performance
            $table->index('trip_id');
            $table->index('chat_session_id');
            $table->index('recommendation_type');
            $table->index('status');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_recommendations');
    }
};
