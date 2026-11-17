<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\MobilePurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileBackendContractsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_device_token_registration_is_idempotent_and_user_scoped(): void
    {
        $user = User::factory()->create();
        $payload = ['provider' => 'fcm', 'token' => 'opaque-device-token', 'platform' => 'android', 'app_version' => '0.1.0+1'];

        $this->actingAs($user)->postJson('/api/v1/mobile/device-tokens', $payload)->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/mobile/device-tokens', [...$payload, 'app_version' => '0.1.0+2'])->assertOk();

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', ['user_id' => $user->id, 'platform' => 'android', 'app_version' => '0.1.0+2']);
        $this->assertNotSame('opaque-device-token', DeviceToken::query()->sole()->getRawOriginal('token'));
    }

    public function test_device_token_can_only_be_removed_by_its_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $payload = ['provider' => 'fcm', 'token' => 'opaque-device-token', 'platform' => 'ios'];

        $this->actingAs($owner)->postJson('/api/v1/mobile/device-tokens', $payload)->assertCreated();
        $this->actingAs($otherUser)->deleteJson('/api/v1/mobile/device-tokens', $payload)->assertOk();
        $this->assertDatabaseCount('device_tokens', 1);
    }

    public function test_notification_preferences_are_user_level_and_default_to_enabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/mobile/notification-preferences')
            ->assertOk()->assertJsonPath('data.enabled', true);

        $this->actingAs($user)->putJson('/api/v1/mobile/notification-preferences', [
            'enabled' => false,
            'sermon_processing' => true,
            'devotional_reminders' => false,
            'subscription_updates' => true,
        ])->assertOk()->assertJsonPath('data.devotional_reminders', false);

        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id, 'enabled' => false]);
    }

    public function test_purchase_verification_fails_closed_without_credentials(): void
    {
        config(['mobile_purchases.app_store.enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/mobile/purchases/verify', [
            'store' => 'app_store',
            'product_id' => 'luma_monthly',
            'transaction_id' => 'transaction-1',
            'verification_data' => 'opaque-receipt',
        ])->assertUnprocessable()->assertJsonValidationErrors('store');
    }

    public function test_active_mobile_purchase_is_reflected_in_entitlements(): void
    {
        $user = User::factory()->create();
        MobilePurchase::query()->create([
            'user_id' => $user->id,
            'store' => 'app_store',
            'product_id' => 'luma_monthly',
            'transaction_id' => 'transaction-1',
            'status' => 'active',
            'verification_data' => 'opaque-receipt',
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)->getJson('/api/v1/mobile/purchases/status')
            ->assertOk()->assertJsonPath('data.membership.plan', 'premium');
    }

    public function test_account_deletion_requires_exact_confirmation_and_removes_owned_data(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/v1/user', ['confirmation' => 'delete'])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($user)->deleteJson('/api/v1/user', ['confirmation' => 'DELETE'])
            ->assertOk()->assertJsonPath('message', 'Account permanently deleted.');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $user->id]);
    }
}
