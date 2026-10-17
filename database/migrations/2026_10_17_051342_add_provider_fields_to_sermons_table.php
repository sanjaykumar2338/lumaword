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
        Schema::table('sermons', function (Blueprint $table) {
            $table->string('provider_video_id', 32)->nullable()->after('source_provider');
            $table->string('provider_caption_id')->nullable()->after('provider_video_id');
            $table->timestamp('provider_retrieved_at')->nullable()->after('provider_caption_id');

            $table->index(['source_provider', 'provider_video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sermons', function (Blueprint $table) {
            $table->dropIndex(['source_provider', 'provider_video_id']);
            $table->dropColumn(['provider_video_id', 'provider_caption_id', 'provider_retrieved_at']);
        });
    }
};
