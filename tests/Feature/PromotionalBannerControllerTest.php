<?php

namespace Tests\Feature;

use App\Models\PromotionalBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PromotionalBannerControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_403_when_non_admin_creates_banner(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/api/v1/admin/promotional-banners', $this->validPayload())->assertForbidden();
        $this->assertDatabaseCount('promotional_banners', 0);
    }

    public function test_admin_can_create_disabled_https_banner(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->postJson('/api/v1/admin/promotional-banners', $this->validPayload())
            ->assertCreated()->assertJsonPath('data.is_enabled', false);
        $this->assertDatabaseHas('promotional_banners', ['title' => 'Store placeholder', 'is_enabled' => false]);
    }

    public function test_rejects_non_https_destination_url(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->postJson('/api/v1/admin/promotional-banners', [
            ...$this->validPayload(), 'destination_url' => 'javascript:alert(1)',
        ])->assertUnprocessable()->assertJsonValidationErrors('destination_url');
    }

    public function test_free_user_sees_only_active_banner(): void
    {
        PromotionalBanner::query()->create([...$this->validPayload(), 'is_enabled' => true]);
        PromotionalBanner::query()->create([...$this->validPayload(), 'title' => 'Disabled']);

        $this->actingAs(User::factory()->create())->getJson('/api/v1/promotional-banners?placement=dashboard')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Store placeholder');
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return ['title' => 'Store placeholder', 'description' => 'Placeholder promotion.', 'image_url' => null,
            'image_alt' => null, 'destination_url' => 'https://example.com/store', 'placement' => 'dashboard', 'is_enabled' => false];
    }
}
