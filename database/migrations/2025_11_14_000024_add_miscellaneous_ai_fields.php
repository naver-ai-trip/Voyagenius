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
        Schema::table('checklist_items', function (Blueprint $table) {
            if (!Schema::hasColumn('checklist_items', 'checked_at')) {
                $table->timestamp('checked_at')->nullable()->after('is_checked');
            }
        });

        Schema::table('shares', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('token');
            
            $table->index('expires_at');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name'); // destination, activity, food, etc.
            
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });

        Schema::table('shares', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
