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
        Schema::create('sermon_companion_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sermon_companion_devotional_plan_id')->constrained(indexName: 'sc_progress_plan_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_day')->default(1);
            $table->json('completed_days')->nullable();
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('midweek_check_in')->nullable();
            $table->timestamps();
            $table->unique('sermon_companion_devotional_plan_id', 'sc_progress_plan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_companion_progress');
    }
};
