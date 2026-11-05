<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\AiUsageReservation;
use App\Models\BibleStudy;
use App\Models\DailyActivity;
use App\Models\Devotional;
use App\Models\Sermon;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_admin_dashboard_api_has_no_authenticated_user(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }

    public function test_returns_403_when_non_admin_opens_dashboard_or_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_dashboard_returns_database_metrics_charts_subscriptions_and_ai_usage(): void
    {
        $this->travelTo('2026-11-05 12:00:00');
        $admin = User::factory()->create(['is_admin' => true, 'created_at' => now()]);
        $premiumUser = User::factory()->create(['created_at' => now()->subDays(2)]);
        User::factory()->create(['created_at' => now()->subDays(40)]);
        Subscription::query()->create([
            'user_id' => $premiumUser->id,
            'type' => 'premium',
            'stripe_id' => 'sub_active_admin_metrics',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);
        Subscription::query()->create([
            'user_id' => $admin->id,
            'type' => 'premium',
            'stripe_id' => 'sub_past_due_admin_metrics',
            'stripe_status' => 'past_due',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);
        Sermon::factory()->for($premiumUser)->create();
        BibleStudy::factory()->for($premiumUser)->create();
        Devotional::factory()->for($premiumUser)->create();
        DailyActivity::factory()->for($premiumUser)->create(['activity_date' => today()]);
        AiUsageReservation::factory()->for($premiumUser)->create([
            'status' => 'completed',
            'cost_micros' => 1250,
            'created_at' => now(),
        ]);
        AiUsageReservation::factory()->for($premiumUser)->create([
            'status' => 'released',
            'feature' => 'devotional',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.metrics.total_users', 3)
            ->assertJsonPath('data.metrics.registrations_today', 1)
            ->assertJsonPath('data.metrics.registrations_7_days', 2)
            ->assertJsonPath('data.metrics.free_users', 2)
            ->assertJsonPath('data.metrics.premium_users', 1)
            ->assertJsonPath('data.metrics.active_subscriptions', 1)
            ->assertJsonPath('data.metrics.total_sermons', 1)
            ->assertJsonPath('data.metrics.total_bible_studies', 1)
            ->assertJsonPath('data.metrics.total_devotionals', 1)
            ->assertJsonPath('data.metrics.total_ai_requests', 2)
            ->assertJsonPath('data.metrics.estimated_ai_cost_micros', 1250)
            ->assertJsonPath('data.subscriptions.counts.past_due', 1)
            ->assertJsonPath('data.ai_usage.requests_today', 1)
            ->assertJsonPath('data.ai_usage.requests_this_month', 2)
            ->assertJsonPath('data.ai_usage.failed_requests_this_month', 1)
            ->assertJsonCount(7, 'data.registrations.last_7_days')
            ->assertJsonCount(30, 'data.activity.last_30_days')
            ->assertJsonPath('data.activity.last_30_days.29.count', 1);
    }

    public function test_admin_analytics_do_not_modify_existing_user_or_content_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create(['transcript' => 'Existing private sermon content.']);

        $this->actingAs($admin)->getJson('/api/v1/admin/dashboard')->assertOk();

        $this->assertModelExists($user);
        $this->assertModelExists($sermon);
        $this->assertSame('Existing private sermon content.', $sermon->refresh()->transcript);
    }
}
