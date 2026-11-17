<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->text('token');
            $table->string('token_hash', 64);
            $table->string('platform', 16);
            $table->string('app_version', 64)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'token_hash']);
            $table->index(['provider', 'token_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
