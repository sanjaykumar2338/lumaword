<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('store', 32);
            $table->string('product_id', 128);
            $table->string('transaction_id', 255);
            $table->string('original_transaction_id', 255)->nullable();
            $table->string('status', 32);
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('verification_data');
            $table->json('provider_response')->nullable();
            $table->timestamps();

            $table->unique(['store', 'transaction_id']);
            $table->index(['user_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_purchases');
    }
};
