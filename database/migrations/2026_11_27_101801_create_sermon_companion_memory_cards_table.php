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
        Schema::create('sermon_companion_memory_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sermon_companion_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 200);
            $table->string('translation', 32);
            $table->longText('verse_text');
            $table->json('fill_in_blank')->nullable();
            $table->json('correct_answers')->nullable();
            $table->timestamps();
            $table->unique(['sermon_companion_id', 'reference', 'translation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_companion_memory_cards');
    }
};
