<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'rpp_user_plan_day_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('reading_plan_progress')) {
            Schema::create('reading_plan_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_reading_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reading_plan_day_id')->constrained()->cascadeOnDelete();
                $table->timestamp('completed_at');
                $table->timestamps();

                $table->unique(
                    ['user_reading_plan_id', 'reading_plan_day_id'],
                    self::UNIQUE_INDEX,
                );
            });

            return;
        }

        if (! Schema::hasIndex(
            'reading_plan_progress',
            ['user_reading_plan_id', 'reading_plan_day_id'],
            'unique',
        )) {
            Schema::table('reading_plan_progress', function (Blueprint $table) {
                $table->unique(
                    ['user_reading_plan_id', 'reading_plan_day_id'],
                    self::UNIQUE_INDEX,
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_plan_progress');
    }
};
