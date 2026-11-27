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
        Schema::create('sermon_companion_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sermon_companion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('devotional_day_id')->nullable()->constrained('sermon_companion_devotional_days')->nullOnDelete();
            $table->string('entry_type', 32);
            $table->string('client_key', 100)->nullable();
            $table->longText('content');
            $table->timestamps();
            $table->unique(['user_id', 'sermon_companion_id', 'devotional_day_id', 'entry_type', 'client_key'], 'companion_journal_idempotency');
            $table->index(['user_id', 'sermon_companion_id', 'entry_type'], 'sc_journal_user_companion_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_companion_journal_entries');
    }
};
