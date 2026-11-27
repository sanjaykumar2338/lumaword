<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sermon_companion_reminder_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sermon_companion_devotional_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 24);
            $table->date('reminder_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['sermon_companion_devotional_plan_id', 'kind', 'reminder_date'], 'companion_reminder_dedupe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sermon_companion_reminder_deliveries');
    }
};
