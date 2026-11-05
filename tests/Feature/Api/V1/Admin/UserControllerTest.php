<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\AiUsageReservation;
use App\Models\Sermon;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_403_when_non_admin_searches_or_inspects_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/admin/users')->assertForbidden();
        $this->actingAs($user)->getJson("/api/v1/admin/users/{$otherUser->id}")->assertForbidden();
    }

    public function test_admin_can_search_users_by_name_or_email_with_pagination(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(22)->create();
        $match = User::factory()->create(['name' => 'Randy Beta', 'email' => 'randy@example.com']);

        $this->actingAs($admin)->getJson('/api/v1/admin/users?per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 24)
            ->assertJsonCount(10, 'data');

        $this->actingAs($admin)->getJson('/api/v1/admin/users?search=randy%40example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id)
            ->assertJsonPath('data.0.name', 'Randy Beta');
    }

    public function test_admin_user_detail_returns_usage_without_private_content_or_payment_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['password' => 'secret-password', 'stripe_id' => 'cus_private']);
        Sermon::factory()->for($user)->create(['transcript' => 'Private transcript that must never be returned.']);
        AiUsageReservation::factory()->for($user)->create(['status' => 'completed', 'cost_micros' => 875]);
        Subscription::query()->create([
            'user_id' => $user->id,
            'type' => 'premium',
            'stripe_id' => 'sub_troubleshooting',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin)->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.membership', 'premium')
            ->assertJsonPath('data.usage.sermons', 1)
            ->assertJsonPath('data.usage.ai_requests', 1)
            ->assertJsonPath('data.subscriptions.0.stripe_id', 'sub_troubleshooting')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.stripe_id')
            ->assertJsonMissingPath('data.pm_last_four');
        $this->assertStringNotContainsString('Private transcript', $response->getContent());
        $this->assertStringNotContainsString('cus_private', $response->getContent());
    }
}
