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
        Schema::create('sermon_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('sermon_conversations')->cascadeOnDelete();
            $table->foreignId('reply_to_message_id')->nullable()->constrained('sermon_messages')->cascadeOnDelete();
            $table->string('role', 16);
            $table->longText('content');
            $table->json('sources')->nullable();
            $table->string('status', 16)->default('completed');
            $table->uuid('client_request_id')->nullable();
            $table->string('ai_provider', 50)->nullable();
            $table->string('ai_model', 100)->nullable();
            $table->string('ai_response_id')->nullable();
            $table->json('usage')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'client_request_id']);
            $table->unique('reply_to_message_id');
            $table->index(['conversation_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sermon_messages');
    }
};
