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
        Schema::create('itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->integer('day_number'); // Which day of the trip (1, 2, 3...)
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->foreignId('place_id')->nullable()->constrained()->onDelete('set null');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'day_number']);
            $table->index('place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_items');
    }
};
