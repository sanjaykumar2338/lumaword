<?php

namespace Tests\Feature;

use App\Contracts\WebPushGateway;
use App\Jobs\SendSermonCompanionReminder;
use App\Models\SermonCompanionProgress;
use App\Models\SermonCompanionReminderDelivery;
use App\Models\User;
use App\Services\NativeNotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SermonCompanionReminderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_scheduler_queues_daily_and_midweek_companion_reminders_once(): void
    {
        config(['services.firebase.enabled' => true]);
        Queue::fake([SendSermonCompanionReminder::class]);
        $this->travelTo('2026-11-27 04:00:00 UTC');
        $user = User::factory()->create();
        $user->notificationPreference()->create(['enabled' => true, 'sermon_companion_reminders' => true, 'sermon_companion_midweek_check_ins' => true, 'reminder_time' => '08:00', 'timezone' => 'Asia/Kolkata']);
        $companion = $user->sermonCompanions()->create(['title' => 'Walk', 'status' => 'completed']);
        $plan = $companion->devotionalPlans()->create(['user_id' => $user->id, 'days_count' => 5, 'status' => 'completed']);
        SermonCompanionProgress::query()->create(['sermon_companion_devotional_plan_id' => $plan->id, 'user_id' => $user->id, 'current_day' => 3, 'completed_days' => [1, 2], 'completion_percentage' => 40]);
        $this->artisan('app:dispatch-sermon-companion-reminders')->assertExitCode(0);
        Queue::assertPushed(SendSermonCompanionReminder::class, 2);
        $this->assertDatabaseCount('sermon_companion_reminder_deliveries', 2);
        $this->artisan('app:dispatch-sermon-companion-reminders')->assertExitCode(0);
        Queue::assertPushed(SendSermonCompanionReminder::class, 2);
        $this->assertDatabaseCount('sermon_companion_reminder_deliveries', 2);
    }

    public function test_disabled_companion_reminders_are_not_queued(): void
    {
        config(['services.firebase.enabled' => true]);
        Queue::fake([SendSermonCompanionReminder::class]);
        $this->travelTo('2026-11-27 04:00:00 UTC');
        $user = User::factory()->create();
        $user->notificationPreference()->create(['enabled' => true, 'sermon_companion_reminders' => false, 'sermon_companion_midweek_check_ins' => false, 'reminder_time' => '08:00', 'timezone' => 'Asia/Kolkata']);
        $companion = $user->sermonCompanions()->create(['title' => 'Walk', 'status' => 'completed']);
        $plan = $companion->devotionalPlans()->create(['user_id' => $user->id, 'days_count' => 5, 'status' => 'completed']);
        SermonCompanionProgress::query()->create(['sermon_companion_devotional_plan_id' => $plan->id, 'user_id' => $user->id, 'current_day' => 2, 'completed_days' => [1], 'completion_percentage' => 20]);
        $this->artisan('app:dispatch-sermon-companion-reminders')->assertExitCode(0);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('sermon_companion_reminder_deliveries', 0);
    }

    public function test_midweek_delivery_marks_the_check_in_after_native_delivery(): void
    {
        $user = User::factory()->create();
        $companion = $user->sermonCompanions()->create(['title' => 'Walk', 'status' => 'completed']);
        $plan = $companion->devotionalPlans()->create(['user_id' => $user->id, 'days_count' => 5, 'status' => 'completed']);
        $progress = SermonCompanionProgress::query()->create(['sermon_companion_devotional_plan_id' => $plan->id, 'user_id' => $user->id, 'current_day' => 3, 'completed_days' => [1, 2], 'completion_percentage' => 40]);
        $delivery = SermonCompanionReminderDelivery::query()->create(['sermon_companion_devotional_plan_id' => $plan->id, 'user_id' => $user->id, 'kind' => 'midweek', 'reminder_date' => '2026-11-27']);
        $this->mock(NativeNotificationService::class, function ($mock): void {
            $mock->shouldReceive('send')->once()->andReturn(1);
        });
        (new SendSermonCompanionReminder($delivery))->handle(app(WebPushGateway::class), app(NativeNotificationService::class));
        $this->assertNotNull($delivery->refresh()->sent_at);
        $this->assertNotNull($progress->refresh()->midweek_check_in);
    }
}
