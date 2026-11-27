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
        Schema::create('sermon_companion_devotional_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sermon_companion_devotional_plan_id')->constrained(indexName: 'sc_days_plan_fk')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('title', 180);
            $table->string('theme', 255);
            $table->string('scripture_reference', 200);
            $table->string('translation', 32);
            $table->longText('scripture_text');
            $table->longText('reflection');
            $table->text('reflection_question');
            $table->text('practical_application');
            $table->text('prayer');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['sermon_companion_devotional_plan_id', 'day_number'], 'sc_days_plan_day_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_companion_devotional_days');
    }
};
