<?php

namespace Tests\Feature;

use App\Jobs\SendDevotionalReminder;
use App\Models\DailyActivity;
use App\Models\DevotionalReminderPreference;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchDevotionalRemindersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reminder_is_queued_at_the_configured_local_time(): void
    {
        $this->travelTo('2026-10-21 01:15:00 UTC');
        $this->configureWebPush();
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create([
            'reminder_time' => '06:45',
            'timezone' => 'Asia/Kolkata',
        ]);
        PushSubscription::factory()->for($user)->create();
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(SendDevotionalReminder::class, 1);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 1);
        $delivery = $user->devotionalReminderDeliveries()->sole();
        $this->assertSame('2026-10-21', $delivery->reminder_date->toDateString());
    }

    public function test_reminder_is_queued_when_the_scheduler_runs_late(): void
    {
        $this->travelTo('2026-10-21 08:07:00 UTC');
        $this->configureWebPush();
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['reminder_time' => '08:00']);
        PushSubscription::factory()->for($user)->create();
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(SendDevotionalReminder::class, 1);
        $delivery = $user->devotionalReminderDeliveries()->sole();
        $this->assertSame('2026-10-21', $delivery->reminder_date->toDateString());
    }

    public function test_reminder_is_not_queued_before_the_configured_local_time(): void
    {
        $this->travelTo('2026-10-21 07:59:00 UTC');
        $this->configureWebPush();
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['reminder_time' => '08:00']);
        PushSubscription::factory()->for($user)->create();
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_due_local_reminder_is_queued_only_once_per_local_date(): void
    {
        $this->travelTo('2026-10-21 08:07:00 UTC');
        $this->configureWebPush();
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['reminder_time' => '08:00']);
        PushSubscription::factory()->for($user)->create();
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();
        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(SendDevotionalReminder::class, 1);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 1);
    }

    public function test_completed_devotional_activity_suppresses_the_reminder(): void
    {
        $this->travelTo('2026-10-21 08:07:00 UTC');
        $this->configureWebPush();
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['reminder_time' => '08:00']);
        PushSubscription::factory()->for($user)->create();
        DailyActivity::factory()->for($user)->create(['activity_date' => '2026-10-21', 'devotional_read' => true]);
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_local_timezone_controls_due_time_and_delivery_date_across_utc_date_boundaries(): void
    {
        $this->travelTo('2026-10-21 10:00:00 UTC');
        $this->configureWebPush();
        $dueUser = User::factory()->create();
        DevotionalReminderPreference::factory()->for($dueUser)->create([
            'reminder_time' => '00:00',
            'timezone' => 'Pacific/Kiritimati',
        ]);
        PushSubscription::factory()->for($dueUser)->create();
        $notDueUser = User::factory()->create();
        DevotionalReminderPreference::factory()->for($notDueUser)->create([
            'reminder_time' => '00:01',
            'timezone' => 'Pacific/Honolulu',
        ]);
        PushSubscription::factory()->for($notDueUser)->create();
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(
            SendDevotionalReminder::class,
            fn (SendDevotionalReminder $job): bool => $job->delivery->user_id === $dueUser->id,
        );
        $delivery = $dueUser->devotionalReminderDeliveries()->sole();
        $this->assertSame('2026-10-22', $delivery->reminder_date->toDateString());
        $this->assertDatabaseMissing('devotional_reminder_deliveries', ['user_id' => $notDueUser->id]);
    }

    public function test_disabled_reminders_and_users_without_push_subscriptions_are_skipped(): void
    {
        $this->travelTo('2026-10-21 08:07:00 UTC');
        $this->configureWebPush();
        $disabledUser = User::factory()->create();
        DevotionalReminderPreference::factory()->for($disabledUser)->create([
            'enabled' => false,
            'reminder_time' => '08:00',
        ]);
        PushSubscription::factory()->for($disabledUser)->create();
        $unsubscribedUser = User::factory()->create();
        DevotionalReminderPreference::factory()->for($unsubscribedUser)->create(['reminder_time' => '08:00']);
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    private function configureWebPush(): void
    {
        config(['services.web_push' => [
            'vapid_subject' => 'mailto:test@example.com',
            'vapid_public_key' => 'public-key',
            'vapid_private_key' => 'private-key',
        ]]);
    }
}
