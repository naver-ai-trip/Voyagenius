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
        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'chat_session_id')) {
                return;
            }
            
            $table->foreignId('chat_session_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            $table->decimal('budget_min', 10, 2)->nullable()->after('progress');
            $table->decimal('budget_max', 10, 2)->nullable()->after('budget_min');
            $table->string('currency', 3)->nullable()->after('budget_max'); // USD, KRW, EUR, etc.
            
            $table->index('chat_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['chat_session_id']);
            $table->dropIndex(['chat_session_id']);
            $table->dropColumn(['chat_session_id', 'budget_min', 'budget_max', 'currency']);
        });
    }
};
