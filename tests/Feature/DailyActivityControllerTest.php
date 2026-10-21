<?php

namespace Tests\Feature;

use App\Models\DailyActivity;
use App\Models\DevotionalReminderPreference;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\User;
use App\Models\UserReadingPlan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DailyActivityControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_activity_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/daily-activity')->assertUnauthorized();
        $this->postJson('/api/v1/devotionals/activity')->assertUnauthorized();
    }

    public function test_meaningful_activity_counts_once_per_local_calendar_day(): void
    {
        $this->travelTo('2026-10-21 20:00:00 UTC');
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['timezone' => 'Asia/Kolkata']);

        $this->actingAs($user)->postJson('/api/v1/devotionals/activity')->assertOk();
        $this->actingAs($user)->postJson('/api/v1/devotionals/activity')->assertOk();

        $this->assertDatabaseCount('daily_activities', 1);
        $activity = DailyActivity::query()->sole();
        $this->assertSame('2026-10-22', $activity->activity_date->toDateString());
        $this->assertSame('Asia/Kolkata', $activity->timezone);
        $this->assertTrue($activity->devotional_read);
    }

    public function test_summary_calculates_current_longest_total_and_last_seven_days(): void
    {
        $this->travelTo('2026-10-21 12:00:00 UTC');
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['timezone' => 'UTC']);

        foreach (['2026-10-14', '2026-10-15', '2026-10-17', '2026-10-19', '2026-10-20'] as $date) {
            DailyActivity::factory()->for($user)->create(['activity_date' => $date]);
        }

        $this->actingAs($user)->getJson('/api/v1/daily-activity')
            ->assertOk()
            ->assertJsonPath('data.current_streak', 2)
            ->assertJsonPath('data.longest_streak', 2)
            ->assertJsonPath('data.total_active_days', 5)
            ->assertJsonPath('data.last_seven_days.5.date', '2026-10-20')
            ->assertJsonPath('data.last_seven_days.5.active', true)
            ->assertJsonPath('data.last_seven_days.6.active', false);
    }

    public function test_current_streak_resets_after_a_full_missed_day(): void
    {
        $this->travelTo('2026-10-21 12:00:00 UTC');
        $user = User::factory()->create();
        DailyActivity::factory()->for($user)->create(['activity_date' => '2026-10-19']);

        $this->actingAs($user)->getJson('/api/v1/daily-activity')
            ->assertOk()
            ->assertJsonPath('data.current_streak', 0)
            ->assertJsonPath('data.longest_streak', 1);
    }

    public function test_timezone_changes_do_not_rewrite_historical_activity_dates(): void
    {
        $this->travelTo('2026-10-21 02:00:00 UTC');
        $user = User::factory()->create();
        $preference = DevotionalReminderPreference::factory()->for($user)->create(['timezone' => 'America/Los_Angeles']);

        $this->actingAs($user)->postJson('/api/v1/devotionals/activity')->assertOk();
        $preference->update(['timezone' => 'Asia/Tokyo']);
        $this->actingAs($user)->postJson('/api/v1/devotionals/activity')->assertOk();

        $this->assertDatabaseCount('daily_activities', 2);
        $activities = DailyActivity::query()->orderBy('activity_date')->get();
        $this->assertSame(['2026-10-20', '2026-10-21'], $activities->pluck('activity_date')->map->toDateString()->all());
        $this->assertSame(['America/Los_Angeles', 'Asia/Tokyo'], $activities->pluck('timezone')->all());
    }

    public function test_completing_a_reading_plan_day_records_activity_without_duplicates(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create();
        $day = ReadingPlanDay::factory()->for($plan)->create();
        $enrollment = UserReadingPlan::factory()->for($user)->for($plan, 'readingPlan')->create();
        $url = "/api/v1/reading-plan-enrollments/{$enrollment->id}/days/{$day->id}";

        $this->actingAs($user)->putJson($url, ['completed' => true])->assertOk();
        $this->actingAs($user)->putJson($url, ['completed' => true])->assertOk();

        $this->assertDatabaseCount('daily_activities', 1);
        $this->assertDatabaseHas('daily_activities', ['user_id' => $user->id, 'reading_plan_completed' => true]);
    }
}
