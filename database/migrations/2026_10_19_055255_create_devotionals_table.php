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
        Schema::create('devotionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sermon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type', 20);
            $table->string('title');
            $table->string('scripture_key');
            $table->string('scripture_reference');
            $table->string('translation', 16)->default('WEB');
            $table->text('scripture_text');
            $table->text('reflection');
            $table->text('application');
            $table->text('prayer');
            $table->string('ai_provider', 30);
            $table->string('ai_model');
            $table->string('prompt_version', 40);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['user_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devotionals');
    }
};
