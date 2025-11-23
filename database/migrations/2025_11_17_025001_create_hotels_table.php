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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('amadeus_hotel_id')->unique(); // Amadeus property code (e.g., 'RTPAR001')
            $table->string('name');
            $table->string('chain_code')->nullable(); // Hotel chain code (e.g., 'RT')
            $table->string('dupe_id')->nullable(); // Duplicate ID
            $table->decimal('rating', 3, 1)->nullable(); // Hotel rating (e.g., 4.5)
            $table->string('city_code', 3)->nullable(); // IATA city code (e.g., 'PAR', 'NYC')
            $table->decimal('latitude', 10, 7)->nullable(); // Latitude with 7 decimal precision
            $table->decimal('longitude', 10, 7)->nullable(); // Longitude with 7 decimal precision
            $table->json('address')->nullable(); // Address object (lines, postalCode, cityName, countryCode)
            $table->json('contact')->nullable(); // Contact object (phone, fax)
            $table->json('description')->nullable(); // Description object (lang, text)
            $table->json('amenities')->nullable(); // Array of amenities
            $table->json('media')->nullable(); // Array of media objects (uri, category)
            $table->timestamps();

            // Indexes for location-based queries
            $table->index(['latitude', 'longitude']);
            $table->index('city_code');
            $table->index('chain_code');
            $table->index('rating');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
