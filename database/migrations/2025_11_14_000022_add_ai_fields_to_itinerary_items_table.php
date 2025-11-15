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
        Schema::table('itinerary_items', function (Blueprint $table) {
            if (Schema::hasColumn('itinerary_items', 'recommendation_id')) {
                return;
            }
            
            $table->foreignId('recommendation_id')->nullable()->after('trip_id')->constrained('trip_recommendations')->onDelete('set null');
            $table->decimal('estimated_cost', 10, 2)->nullable()->after('note');
            $table->string('status')->default('planned')->after('estimated_cost'); // planned, confirmed, completed, skipped
            
            $table->index('recommendation_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->dropForeign(['recommendation_id']);
            $table->dropIndex(['recommendation_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['recommendation_id', 'estimated_cost', 'status']);
        });
    }
};
