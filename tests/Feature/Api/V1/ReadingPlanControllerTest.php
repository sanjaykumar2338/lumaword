<?php

namespace Tests\Feature\Api\V1;

use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\User;
use App\Models\UserReadingPlan;
use Database\Seeders\ReadingPlanSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_user_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/reading-plans')->assertUnauthorized();
    }

    public function test_catalog_returns_seven_fourteen_and_thirty_day_plans_with_web_scripture(): void
    {
        $this->seed(ReadingPlanSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/reading-plans');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.duration_days', 7)
            ->assertJsonPath('data.1.duration_days', 14)
            ->assertJsonPath('data.2.duration_days', 30)
            ->assertJsonPath('data.0.days.0.translation', 'WEB')
            ->assertJsonPath('data.0.days.0.scripture_reference', 'John 3:16-21');
    }

    public function test_starting_same_plan_twice_resumes_one_enrollment_without_duplicates(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/reading-plans/{$plan->id}/enrollments")
            ->assertCreated();
        $this->actingAs($user)
            ->postJson("/api/v1/reading-plans/{$plan->id}/enrollments")
            ->assertOk();

        $this->assertDatabaseCount('user_reading_plans', 1);
    }

    public function test_marking_day_complete_twice_returns_and_persists_one_progress_entry(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['duration_days' => 7]);
        $day = ReadingPlanDay::factory()->for($plan)->create();
        $enrollment = UserReadingPlan::factory()->for($user)->for($plan, 'readingPlan')->create();

        $url = "/api/v1/reading-plan-enrollments/{$enrollment->id}/days/{$day->id}";
        $this->actingAs($user)->putJson($url, ['completed' => true])
            ->assertOk()
            ->assertJsonPath('data.completed_days', 1)
            ->assertJsonPath('data.completed_day_ids.0', $day->id);
        $this->actingAs($user)->putJson($url, ['completed' => true])
            ->assertOk()
            ->assertJsonPath('data.completed_days', 1)
            ->assertJsonPath('data.completed_day_ids.0', $day->id);

        $this->actingAs($user)->getJson('/api/v1/reading-plans')
            ->assertOk()
            ->assertJsonPath('data.0.enrollment.completed_day_ids.0', $day->id);

        $this->assertDatabaseCount('reading_plan_progress', 1);
    }

    public function test_finishing_every_day_marks_plan_complete_and_unchecking_resumes_it(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['duration_days' => 2]);
        $days = collect([
            ReadingPlanDay::factory()->for($plan)->create(['day_number' => 1]),
            ReadingPlanDay::factory()->for($plan)->create(['day_number' => 2]),
        ]);
        $enrollment = UserReadingPlan::factory()->for($user)->for($plan, 'readingPlan')->create();

        foreach ($days as $day) {
            $this->actingAs($user)->putJson(
                "/api/v1/reading-plan-enrollments/{$enrollment->id}/days/{$day->id}",
                ['completed' => true],
            )->assertOk();
        }

        $this->assertNotNull($enrollment->refresh()->completed_at);

        $this->actingAs($user)->putJson(
            "/api/v1/reading-plan-enrollments/{$enrollment->id}/days/{$days->first()->id}",
            ['completed' => false],
        )
            ->assertOk()
            ->assertJsonPath('data.completed_at', null)
            ->assertJsonPath('data.completed_days', 1)
            ->assertJsonPath('data.completed_day_ids.0', $days->last()->id);

        $this->assertDatabaseCount('reading_plan_progress', 1);
        $this->assertDatabaseMissing('reading_plan_progress', [
            'user_reading_plan_id' => $enrollment->id,
            'reading_plan_day_id' => $days->first()->id,
        ]);
    }

    public function test_user_cannot_update_another_users_progress(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = ReadingPlan::factory()->create();
        $day = ReadingPlanDay::factory()->for($plan)->create();
        $enrollment = UserReadingPlan::factory()->for($owner)->for($plan, 'readingPlan')->create();

        $this->actingAs($otherUser)->putJson(
            "/api/v1/reading-plan-enrollments/{$enrollment->id}/days/{$day->id}",
            ['completed' => true],
        )->assertForbidden();
        $this->assertDatabaseCount('reading_plan_progress', 0);
    }

    public function test_day_from_different_plan_returns_404_and_creates_no_progress(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create();
        $otherPlan = ReadingPlan::factory()->create();
        $otherDay = ReadingPlanDay::factory()->for($otherPlan)->create();
        $enrollment = UserReadingPlan::factory()->for($user)->for($plan, 'readingPlan')->create();

        $this->actingAs($user)->putJson(
            "/api/v1/reading-plan-enrollments/{$enrollment->id}/days/{$otherDay->id}",
            ['completed' => true],
        )->assertNotFound();
        $this->assertDatabaseCount('reading_plan_progress', 0);
    }
}
