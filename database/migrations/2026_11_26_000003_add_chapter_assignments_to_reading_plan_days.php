<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_plan_days', function (Blueprint $table): void {
            $table->json('chapter_assignments')->nullable()->after('scripture_reference');
        });
    }

    public function down(): void
    {
        Schema::table('reading_plan_days', function (Blueprint $table): void {
            $table->dropColumn('chapter_assignments');
        });
    }
};
