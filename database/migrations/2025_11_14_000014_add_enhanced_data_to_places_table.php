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
            $table->json('naver_data')->nullable()->after('category');
            $table->json('custom_data')->nullable()->after('naver_data');
            $table->boolean('verified')->default(false)->after('custom_data');
            
            $table->index('verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['verified']);
            $table->dropColumn(['naver_data', 'custom_data', 'verified']);
        });
    }
};
