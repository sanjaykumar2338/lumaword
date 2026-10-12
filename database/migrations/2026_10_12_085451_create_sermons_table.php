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
        Schema::create('sermons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('source_type', 24);
            $table->text('source_url')->nullable();
            $table->string('source_provider', 80)->nullable();
            $table->string('processing_status', 32)->default('submitted');
            $table->text('processing_error')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('transcript_source', 24)->default('none');
            $table->string('transcript_language', 12)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_original_name')->nullable();
            $table->string('media_mime', 100)->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->unsignedSmallInteger('processing_attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'processing_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermons');
    }
};
