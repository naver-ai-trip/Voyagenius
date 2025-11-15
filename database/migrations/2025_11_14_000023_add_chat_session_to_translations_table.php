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
        Schema::table('translations', function (Blueprint $table) {
            if (Schema::hasColumn('translations', 'chat_session_id')) {
                return;
            }
            
            $table->foreignId('chat_session_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            
            $table->index('chat_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropForeign(['chat_session_id']);
            $table->dropIndex(['chat_session_id']);
            $table->dropColumn('chat_session_id');
        });
    }
};
