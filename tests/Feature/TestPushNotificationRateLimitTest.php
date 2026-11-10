<?php

namespace Tests\Feature;

use App\Contracts\WebPushGateway;
use App\Data\WebPushDeliveryResult;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TestPushNotificationRateLimitTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_rate_limit_response_includes_a_retry_period_for_test_notifications(): void
    {
        $user = User::factory()->create();
        $subscription = PushSubscription::factory()->for($user)->create();
        $this->mock(WebPushGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->times(3)->andReturn(new WebPushDeliveryResult(true));
        });

        $this->freezeTime();
        $this->actingAs($user);
        $payload = ['endpoint' => $subscription->endpoint];

        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertOk();
        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertOk();
        $this->postJson('/api/v1/push-subscriptions/test', $payload)->assertOk();

        $this->postJson('/api/v1/push-subscriptions/test', $payload)
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Too many test notifications. Please try again in 10 minutes.')
            ->assertJsonPath('retry_after', 600)
            ->assertHeader('Retry-After', '600');
    }
}
