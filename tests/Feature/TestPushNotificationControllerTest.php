<?php

namespace Tests\Feature;

use App\Contracts\WebPushGateway;
use App\Data\WebPushDeliveryResult;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class TestPushNotificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_endpoint_requires_authentication(): void
    {
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('send');
        });

        $this->postJson('/api/v1/push-subscriptions/test', [
            'endpoint' => 'https://push.example.test/subscriptions/browser-1',
        ])->assertUnauthorized();
    }

    public function test_user_cannot_send_to_another_users_subscription(): void
    {
        $owner = User::factory()->create();
        $requestingUser = User::factory()->create();
        $subscription = PushSubscription::factory()->for($owner)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('send');
        });

        $this->actingAs($requestingUser)->postJson('/api/v1/push-subscriptions/test', [
            'endpoint' => $subscription->endpoint,
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'No active push subscription was found for this browser.');

        $this->assertModelExists($subscription);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_user_can_send_a_test_notification_to_their_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock) use ($subscription): void {
            $mock->shouldReceive('send')->once()->withArgs(function (PushSubscription $sentSubscription, string $payload) use ($subscription): bool {
                return $sentSubscription->is($subscription)
                    && json_decode($payload, true, flags: JSON_THROW_ON_ERROR) === [
                        'title' => 'Luma Test Notification',
                        'body' => 'Your browser notifications are working correctly!',
                        'url' => '/devotionals',
                    ];
            })->andReturn(new WebPushDeliveryResult(true));
        });

        $this->actingAs($user)->postJson('/api/v1/push-subscriptions/test', [
            'endpoint' => $subscription->endpoint,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Test notification sent successfully.');

        $this->assertModelExists($subscription);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_expired_subscription_is_removed_and_returns_410(): void
    {
        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn(new WebPushDeliveryResult(false, true));
        });

        $this->actingAs($user)->postJson('/api/v1/push-subscriptions/test', [
            'endpoint' => $subscription->endpoint,
        ])
            ->assertGone()
            ->assertJsonPath('message', 'This browser’s push subscription has expired. Enable browser notifications again and retry.');

        $this->assertModelMissing($subscription);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_failed_delivery_returns_502_and_preserves_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn(new WebPushDeliveryResult(false));
        });

        $this->actingAs($user)->postJson('/api/v1/push-subscriptions/test', [
            'endpoint' => $subscription->endpoint,
        ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'The test notification could not be delivered. Please try again.');

        $this->assertModelExists($subscription);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_gateway_exception_is_reported_and_returns_502(): void
    {
        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->for($user)->create();
        Exceptions::fake();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andThrow(new RuntimeException('Push service unavailable.'));
        });

        $this->actingAs($user)->postJson('/api/v1/push-subscriptions/test', [
            'endpoint' => $subscription->endpoint,
        ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'The test notification could not be delivered. Please try again.');

        Exceptions::assertReported(RuntimeException::class);
        $this->assertModelExists($subscription);
        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }

    public function test_user_is_limited_to_three_test_notifications_per_ten_minutes(): void
    {
        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->times(4)->andReturn(new WebPushDeliveryResult(true));
        });

        $this->actingAs($user);
        $payload = ['endpoint' => $subscription->endpoint];

        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertOk();
        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertOk();
        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertOk();
        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertTooManyRequests();
        $this->travel(10)->minutes();
        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertOk();

        $this->assertDatabaseCount('devotional_reminder_deliveries', 0);
    }
}
