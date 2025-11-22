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
        Schema::table('places', function (Blueprint $table) {
            // Drop naver_place_id column and its unique index
            $table->dropUnique(['naver_place_id']);
            $table->dropColumn('naver_place_id');
            
            // Add composite unique index on lat/lng
            // This ensures a place with same coordinates can't be added twice
            $table->unique(['lat', 'lng'], 'places_lat_lng_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // Restore naver_place_id column
            $table->string('naver_place_id')->unique()->after('id');
            
            // Drop composite unique index
            $table->dropUnique('places_lat_lng_unique');
        });
    }
};
