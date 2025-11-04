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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('destination_country');
            $table->string('destination_city');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('planning'); // planning, ongoing, completed, cancelled
            $table->boolean('is_group')->default(false);
            $table->string('progress')->nullable(); // itinerary_complete, checklist_ready, etc.
            $table->timestamps();

            // Indexes for common queries
            $table->index('user_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
