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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('source_type'); // text, image, speech
            $table->text('source_text')->nullable(); // Original text if type is 'text'
            $table->string('source_language');
            $table->text('translated_text');
            $table->string('target_language');
            $table->string('file_path')->nullable(); // For image/audio files
            $table->timestamps();

            $table->index('user_id');
            $table->index('source_type');
            $table->index(['source_language', 'target_language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
