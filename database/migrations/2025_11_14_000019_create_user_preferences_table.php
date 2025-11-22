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
        if (Schema::hasTable('user_preferences')) {
            return;
        }

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('preference_type'); // budget, accommodation, activities, food, transport, etc.
            $table->string('preference_key');
            $table->json('preference_value');
            $table->integer('priority')->default(5); // 1-10, higher = more important
            $table->timestamps();

            // Composite index for efficient lookups
            $table->unique(['user_id', 'preference_type', 'preference_key']);
            $table->index('user_id');
            $table->index('preference_type');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
