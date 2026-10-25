<?php

namespace Tests\Feature;

use App\Models\DevotionalReminderPreference;
use App\Models\PushSubscription;
use App\Models\User;
use DateTimeZone;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DevotionalReminderPreferenceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reminder_and_subscription_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/devotional-reminder-preferences')->assertUnauthorized();
        $this->putJson('/api/v1/devotional-reminder-preferences', [])->assertUnauthorized();
        $this->postJson('/api/v1/push-subscriptions', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/push-subscriptions', [])->assertUnauthorized();
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/devotional-reminder-preferences', [
            'enabled' => true,
            'reminder_time' => '06:45',
            'timezone' => 'Asia/Kolkata',
        ])
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.reminder_time', '06:45')
            ->assertJsonPath('data.timezone', 'Asia/Kolkata')
            ->assertJsonPath('data.timezone_configured', true);

        $this->assertDatabaseHas('devotional_reminder_preferences', [
            'user_id' => $user->id,
            'enabled' => true,
            'reminder_time' => '06:45',
            'timezone' => 'Asia/Kolkata',
        ]);
    }

    public function test_new_user_receives_all_supported_timezones_without_persisting_a_default(): void
    {
        $this->travelTo('2026-01-15 12:00:00 UTC');
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/devotional-reminder-preferences')
            ->assertOk()
            ->assertJsonPath('data.timezone', 'UTC')
            ->assertJsonPath('data.timezone_configured', false)
            ->assertJsonFragment(['value' => 'Asia/Kolkata', 'label' => '(UTC+05:30) Asia/Kolkata'])
            ->assertJsonFragment(['value' => 'America/New_York', 'label' => '(UTC-05:00) America/New_York'])
            ->assertJsonFragment(['value' => 'Europe/London', 'label' => '(UTC+00:00) Europe/London']);

        $this->assertCount(
            count(DateTimeZone::listIdentifiers(DateTimeZone::ALL)),
            $response->json('data.timezone_options'),
        );
        $this->assertDatabaseCount('devotional_reminder_preferences', 0);
    }

    public function test_saved_timezone_is_preserved_when_loading_settings(): void
    {
        $user = User::factory()->create();
        DevotionalReminderPreference::factory()->for($user)->create(['timezone' => 'America/New_York']);

        $this->actingAs($user)
            ->getJson('/api/v1/devotional-reminder-preferences')
            ->assertOk()
            ->assertJsonPath('data.timezone', 'America/New_York')
            ->assertJsonPath('data.timezone_configured', true);
    }

    public function test_invalid_time_and_timezone_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/devotional-reminder-preferences', [
            'enabled' => true,
            'reminder_time' => '25:00',
            'timezone' => 'Moon/SeaOfTranquility',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reminder_time', 'timezone']);

        $this->assertDatabaseCount('devotional_reminder_preferences', 0);
    }

    public function test_push_subscription_is_encrypted_and_idempotent(): void
    {
        $user = User::factory()->create();
        $payload = $this->subscriptionPayload();

        $this->actingAs($user)->postJson('/api/v1/push-subscriptions', $payload)->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/push-subscriptions', $payload)->assertOk();

        $subscription = PushSubscription::query()->sole();
        $this->assertSame($payload['endpoint'], $subscription->endpoint);
        $this->assertNotSame($payload['endpoint'], $subscription->getRawOriginal('endpoint'));
        $this->assertNotSame($payload['keys']['auth'], $subscription->getRawOriginal('auth_token'));
        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    public function test_user_can_remove_their_browser_push_subscription(): void
    {
        $user = User::factory()->create();
        $payload = $this->subscriptionPayload();
        $subscription = PushSubscription::factory()->for($user)->create([
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'endpoint' => $payload['endpoint'],
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/push-subscriptions', ['endpoint' => $payload['endpoint']])
            ->assertOk()
            ->assertJsonPath('message', 'Browser notifications disabled.');

        $this->assertModelMissing($subscription);
    }

    public function test_user_cannot_claim_or_delete_another_users_subscription(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $payload = $this->subscriptionPayload();
        $this->actingAs($owner)->postJson('/api/v1/push-subscriptions', $payload)->assertCreated();

        $this->actingAs($otherUser)->postJson('/api/v1/push-subscriptions', $payload)->assertConflict();
        $this->actingAs($otherUser)->deleteJson('/api/v1/push-subscriptions', ['endpoint' => $payload['endpoint']])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $owner->id]);
    }

    /** @return array{endpoint: string, keys: array{p256dh: string, auth: string}, content_encoding: string} */
    private function subscriptionPayload(): array
    {
        return [
            'endpoint' => 'https://push.example.test/subscriptions/browser-1',
            'keys' => [
                'p256dh' => str_repeat('a', 87),
                'auth' => str_repeat('b', 22),
            ],
            'content_encoding' => 'aes128gcm',
        ];
    }
}
