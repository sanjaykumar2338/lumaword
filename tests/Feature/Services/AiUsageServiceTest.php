<?php

namespace Tests\Feature\Services;

use App\Models\AiUsageReservation;
use App\Models\User;
use App\Services\AiUsageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AiUsageServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_idempotent_retry_does_not_double_charge_usage(): void
    {
        config()->set('ai_usage.limits.free.ask_luma', 1);
        $user = User::factory()->create();
        $service = app(AiUsageService::class);

        $first = $service->reserve($user, 'ask_luma', 'request-1');
        $second = $service->reserve($user, 'ask_luma', 'request-1');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('ai_usage_reservations', 1);
    }

    public function test_free_quota_exhaustion_rejects_next_reservation(): void
    {
        config()->set('ai_usage.limits.free.ask_luma', 1);
        $user = User::factory()->create();
        $service = app(AiUsageService::class);
        $service->reserve($user, 'ask_luma', 'request-1');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('reached this month’s AI allowance');
        $service->reserve($user, 'ask_luma', 'request-2');
    }

    public function test_usage_resets_in_next_calendar_month(): void
    {
        config()->set('ai_usage.limits.free.ask_luma', 1);
        $this->travelTo('2026-11-30 12:00:00');
        $user = User::factory()->create();
        $service = app(AiUsageService::class);
        $service->reserve($user, 'ask_luma', 'november');

        $this->travelTo('2026-12-01 00:00:01');
        $reservation = $service->reserve($user, 'ask_luma', 'december');

        $this->assertSame('2026-12-01', $reservation->period_start->toDateString());
        $this->assertSame(2, AiUsageReservation::query()->count());
    }

    public function test_premium_quota_is_enforced_at_configured_limit(): void
    {
        config()->set('ai_usage.limits.free.ask_luma', 1);
        config()->set('ai_usage.limits.premium.ask_luma', 2);
        $user = User::factory()->create();
        Subscription::query()->create(['user_id' => $user->id, 'type' => 'premium', 'stripe_id' => 'sub_premium',
            'stripe_status' => 'active', 'stripe_price' => 'price_test', 'quantity' => 1]);
        $service = app(AiUsageService::class);

        $service->reserve($user, 'ask_luma', 'request-1');
        $service->reserve($user, 'ask_luma', 'request-2');

        $this->expectException(HttpException::class);
        $service->reserve($user, 'ask_luma', 'request-3');
    }
}
