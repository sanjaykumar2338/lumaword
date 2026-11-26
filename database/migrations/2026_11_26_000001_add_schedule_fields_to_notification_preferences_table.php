<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('reading_plan_reminders')->default(true);
            $table->time('reminder_time')->default('08:00');
            $table->string('timezone')->default('UTC');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn(['reading_plan_reminders', 'reminder_time', 'timezone']);
        });
    }
};
