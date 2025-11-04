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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('favoritable'); // Creates favoritable_type and favoritable_id
            $table->timestamps();

            // Unique constraint: user can only favorite an entity once
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
            $table->index('user_id');
            $table->index('favoritable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
