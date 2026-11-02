<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_checkout_has_no_authenticated_user(): void
    {
        $this->postJson('/api/v1/billing/checkout', ['interval' => 'monthly'])->assertUnauthorized();
    }

    public function test_returns_503_when_billing_is_not_enabled(): void
    {
        config()->set('billing.enabled', false);

        $this->actingAs(User::factory()->create())->postJson('/api/v1/billing/checkout', ['interval' => 'monthly'])
            ->assertServiceUnavailable()->assertJsonPath('message', 'Billing is not enabled yet.');
    }

    public function test_new_and_existing_users_receive_effective_free_membership(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/entitlements')->assertOk()
            ->assertJsonPath('data.membership.plan', 'free')->assertJsonPath('data.membership.status', 'free');
    }

    #[DataProvider('subscriptionStates')]
    public function test_subscription_state_determines_effective_plan(string $status, ?string $endsAt, string $expectedPlan): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $user = User::factory()->create();
        Subscription::query()->create([
            'user_id' => $user->id, 'type' => 'premium', 'stripe_id' => 'sub_'.str_replace(' ', '_', $status).$expectedPlan,
            'stripe_status' => $status, 'stripe_price' => 'price_test', 'quantity' => 1, 'ends_at' => $endsAt,
        ]);

        $this->actingAs($user)->getJson('/api/v1/entitlements')->assertOk()
            ->assertJsonPath('data.membership.plan', $expectedPlan);
    }

    /** @return array<string, array{string, string|null, string}> */
    public static function subscriptionStates(): array
    {
        return [
            'active' => ['active', null, 'premium'],
            'trialing' => ['trialing', null, 'premium'],
            'canceling at period end' => ['active', '2026-12-01 00:00:00', 'premium'],
            'past due' => ['past_due', null, 'free'],
            'expired' => ['active', '2026-01-01 00:00:00', 'free'],
            'canceled' => ['canceled', '2026-12-01 00:00:00', 'free'],
        ];
    }
}
