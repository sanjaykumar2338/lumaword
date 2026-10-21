<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\User;
use App\Models\UserReadingPlan;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateReadingPlanProgressTableTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_repairs_a_partially_created_table_without_losing_data_and_can_be_rerun(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create();
        $day = ReadingPlanDay::factory()->for($plan)->create();
        $enrollment = UserReadingPlan::factory()->for($user)->for($plan, 'readingPlan')->create();
        Schema::drop('reading_plan_progress');
        Schema::create('reading_plan_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_reading_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_plan_day_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();
        });
        DB::table('reading_plan_progress')->insert([
            'user_reading_plan_id' => $enrollment->id,
            'reading_plan_day_id' => $day->id,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_19_055259_create_reading_plan_progress_table.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseCount('reading_plan_progress', 1);
        $this->assertContains('rpp_user_plan_day_unique', Schema::getIndexListing('reading_plan_progress'));

        $this->expectException(QueryException::class);

        DB::table('reading_plan_progress')->insert([
            'user_reading_plan_id' => $enrollment->id,
            'reading_plan_day_id' => $day->id,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
