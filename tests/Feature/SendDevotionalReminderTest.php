<?php

namespace Tests\Feature;

use App\Contracts\WebPushGateway;
use App\Data\WebPushDeliveryResult;
use App\Jobs\SendDevotionalReminder;
use App\Models\DailyActivity;
use App\Models\DevotionalReminderDelivery;
use App\Models\DevotionalReminderPreference;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class SendDevotionalReminderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_job_sends_scripture_centered_payload_and_marks_delivery_sent(): void
    {
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create();
        $subscription = PushSubscription::factory()->for($user)->create();
        $delivery = DevotionalReminderDelivery::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock) use ($subscription): void {
            $mock->shouldReceive('send')->once()->withArgs(function (PushSubscription $sentSubscription, string $payload) use ($subscription): bool {
                $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

                return $sentSubscription->is($subscription)
                    && $decoded['title'] === 'Your daily moment with Luma'
                    && $decoded['url'] === '/devotionals'
                    && str_contains($decoded['body'], 'God’s Word');
            })->andReturn(new WebPushDeliveryResult(true));
        });

        (new SendDevotionalReminder($delivery))->handle(app(WebPushGateway::class));

        $this->assertNotNull($delivery->refresh()->sent_at);
    }

    public function test_job_removes_expired_subscription_without_marking_delivery_sent(): void
    {
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create();
        PushSubscription::factory()->for($user)->create();
        $delivery = DevotionalReminderDelivery::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn(new WebPushDeliveryResult(false, true));
        });

        (new SendDevotionalReminder($delivery))->handle(app(WebPushGateway::class));

        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_job_does_not_send_when_reminders_are_disabled(): void
    {
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['enabled' => false]);
        PushSubscription::factory()->for($user)->create();
        $delivery = DevotionalReminderDelivery::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('send');
        });

        (new SendDevotionalReminder($delivery))->handle(app(WebPushGateway::class));

        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_job_rechecks_devotional_completion_before_sending(): void
    {
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create();
        PushSubscription::factory()->for($user)->create();
        $delivery = DevotionalReminderDelivery::factory()->for($user)->create(['reminder_date' => '2026-10-21']);
        DailyActivity::factory()->for($user)->create(['activity_date' => '2026-10-21', 'devotional_read' => true]);
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('send');
        });

        (new SendDevotionalReminder($delivery))->handle(app(WebPushGateway::class));

        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_job_leaves_delivery_unsent_when_push_delivery_fails(): void
    {
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create();
        PushSubscription::factory()->for($user)->create();
        $delivery = DevotionalReminderDelivery::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn(new WebPushDeliveryResult(false));
        });

        $this->expectException(RuntimeException::class);

        try {
            (new SendDevotionalReminder($delivery))->handle(app(WebPushGateway::class));
        } finally {
            $this->assertNull($delivery->refresh()->sent_at);
        }
    }

    public function test_job_uses_progressive_backoff_and_logs_exhausted_delivery(): void
    {
        $user = User::factory()->create();
        $delivery = DevotionalReminderDelivery::factory()->for($user)->create();
        $job = new SendDevotionalReminder($delivery);
        $exception = new RuntimeException('push service unavailable');
        Log::spy();

        $job->failed($exception);

        $this->assertSame([30, 120], $job->backoff);
        Log::shouldHaveReceived('error')->once()->with(
            'Devotional reminder delivery failed after all retries.',
            [
                'delivery_id' => $delivery->id,
                'user_id' => $user->id,
                'reminder_date' => $delivery->reminder_date->toDateString(),
                'exception_class' => RuntimeException::class,
            ],
        );
    }
}
