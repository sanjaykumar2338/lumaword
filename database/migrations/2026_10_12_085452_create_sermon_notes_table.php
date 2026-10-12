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
        Schema::create('sermon_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sermon_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('summary');
            $table->text('main_idea');
            $table->json('outline');
            $table->json('key_points');
            $table->json('scripture_references');
            $table->json('themes');
            $table->json('applications');
            $table->json('discussion_questions');
            $table->string('ai_provider', 50);
            $table->string('ai_model', 100);
            $table->string('ai_response_id')->nullable();
            $table->string('prompt_version', 30);
            $table->json('usage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_notes');
    }
};
