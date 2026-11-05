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
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at', 'users_created_at_index');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['type', 'stripe_status', 'ends_at'], 'subscriptions_admin_status_index');
        });

        Schema::table('ai_usage_reservations', function (Blueprint $table) {
            $table->index(['created_at', 'status'], 'ai_usage_admin_created_status_index');
            $table->index(['feature', 'period_start', 'status'], 'ai_usage_admin_feature_period_index');
        });

        Schema::table('daily_activities', function (Blueprint $table) {
            $table->index('activity_date', 'daily_activities_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_created_at_index');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_admin_status_index');
        });

        Schema::table('ai_usage_reservations', function (Blueprint $table) {
            $table->dropIndex('ai_usage_admin_created_status_index');
            $table->dropIndex('ai_usage_admin_feature_period_index');
        });

        Schema::table('daily_activities', function (Blueprint $table) {
            $table->dropIndex('daily_activities_date_index');
        });
    }
};
