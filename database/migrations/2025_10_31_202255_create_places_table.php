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
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('naver_place_id')->unique(); // NAVER Maps POI ID
            $table->string('name');
            $table->string('address');
            $table->decimal('lat', 10, 7); // Latitude with 7 decimal precision
            $table->decimal('lng', 10, 7); // Longitude with 7 decimal precision
            $table->string('category')->nullable(); // Tourism, Restaurant, etc.
            $table->timestamps();

            // Indexes for location-based queries
            $table->index(['lat', 'lng']);
            $table->index('category');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
