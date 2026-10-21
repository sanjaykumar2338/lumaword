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

    public function test_due_local_reminder_is_queued_only_once(): void
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
        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(SendDevotionalReminder::class, 1);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 1);
        $delivery = $user->devotionalReminderDeliveries()->sole();
        $this->assertSame('2026-10-21', $delivery->reminder_date->toDateString());
    }

    public function test_disabled_and_not_yet_due_reminders_are_not_queued(): void
    {
        $this->travelTo('2026-10-21 08:00:00 UTC');
        $this->configureWebPush();
        $disabledUser = User::factory()->create();
        DevotionalReminderPreference::factory()->for($disabledUser)->create(['enabled' => false, 'reminder_time' => '08:00']);
        PushSubscription::factory()->for($disabledUser)->create();
        $laterUser = User::factory()->create();
        DevotionalReminderPreference::factory()->for($laterUser)->create(['reminder_time' => '09:00']);
        PushSubscription::factory()->for($laterUser)->create();
        Queue::fake([SendDevotionalReminder::class]);

        $this->artisan('devotional-reminders:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_completed_devotional_activity_suppresses_the_reminder(): void
    {
        $this->travelTo('2026-10-21 08:00:00 UTC');
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

    private function configureWebPush(): void
    {
        config(['services.web_push' => [
            'vapid_subject' => 'mailto:test@example.com',
            'vapid_public_key' => 'public-key',
            'vapid_private_key' => 'private-key',
        ]]);
    }
}
