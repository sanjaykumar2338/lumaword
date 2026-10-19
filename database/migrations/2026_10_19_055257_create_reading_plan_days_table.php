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
        Schema::create('reading_plan_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('title');
            $table->string('scripture_key');
            $table->string('scripture_reference');
            $table->timestamps();

            $table->unique(['reading_plan_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_plan_days');
    }
};
