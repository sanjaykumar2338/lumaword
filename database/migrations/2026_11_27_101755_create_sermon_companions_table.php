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
        Schema::create('sermon_companions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('speaker', 180)->nullable();
            $table->string('church_name', 180)->nullable();
            $table->date('sermon_date')->nullable();
            $table->text('youtube_url')->nullable();
            $table->longText('personal_notes')->nullable();
            $table->json('scripture_references')->nullable();
            $table->json('key_takeaways')->nullable();
            $table->longText('personal_reflections')->nullable();
            $table->longText('confirmed_notes')->nullable();
            $table->json('recap')->nullable();
            $table->string('status', 32)->default('draft');
            $table->text('processing_error')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_companions');
    }
};
