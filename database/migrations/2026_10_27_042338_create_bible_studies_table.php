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
        Schema::create('bible_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('scripture_key');
            $table->string('scripture_reference');
            $table->string('translation', 16)->default('WEB');
            $table->text('scripture_text');
            $table->string('depth', 24);
            $table->json('content');
            $table->text('personal_notes')->nullable();
            $table->string('ai_provider', 30);
            $table->string('ai_model');
            $table->string('ai_response_id')->nullable();
            $table->string('prompt_version', 40);
            $table->char('request_hash', 64);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['user_id', 'request_hash']);
            $table->index(['user_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_studies');
    }
};
