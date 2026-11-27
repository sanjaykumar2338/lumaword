<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('sermon_companion_reminders')->default(true);
            $table->boolean('sermon_companion_midweek_check_ins')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn(['sermon_companion_reminders', 'sermon_companion_midweek_check_ins']);
        });
    }
};
