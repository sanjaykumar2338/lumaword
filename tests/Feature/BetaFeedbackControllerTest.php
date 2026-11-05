<?php

namespace Tests\Feature;

use App\Models\BetaFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BetaFeedbackControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_guest_submits_feedback(): void
    {
        $this->postJson('/api/v1/beta-feedback', [
            'category' => 'suggestion',
            'message' => 'A weekly recap would help.',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('beta_feedback', 0);
    }

    public function test_authenticated_user_can_submit_feedback(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/beta-feedback', [
            'category' => 'usability',
            'message' => 'The reading-plan progress was easy to understand.',
            'user_id' => $otherUser->id,
        ])->assertCreated()
            ->assertJsonPath('data.category', 'usability')
            ->assertJsonPath('message', 'Thank you. Your feedback has been shared with the LUMA team.');

        $this->assertDatabaseHas('beta_feedback', [
            'user_id' => $user->id,
            'category' => 'usability',
            'message' => 'The reading-plan progress was easy to understand.',
        ]);
    }

    public function test_rejects_invalid_category_and_missing_message(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/api/v1/beta-feedback', [
            'category' => 'praise',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'message']);

        $this->assertDatabaseCount('beta_feedback', 0);
    }

    public function test_returns_403_when_non_admin_lists_feedback(): void
    {
        BetaFeedback::factory()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/admin/beta-feedback')
            ->assertForbidden();
    }

    public function test_returns_429_after_feedback_rate_limit_is_reached(): void
    {
        $user = User::factory()->create();
        $payload = ['category' => 'other', 'message' => 'A useful beta observation.'];

        for ($submission = 1; $submission <= 5; $submission++) {
            $this->actingAs($user)->postJson('/api/v1/beta-feedback', $payload)->assertCreated();
        }

        $this->actingAs($user)->postJson('/api/v1/beta-feedback', $payload)->assertTooManyRequests();
        $this->assertDatabaseCount('beta_feedback', 5);
    }

    public function test_admin_can_list_paginated_feedback_newest_first(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $older = BetaFeedback::factory()->create(['message' => 'Older feedback', 'created_at' => now()->subDay()]);
        $newer = BetaFeedback::factory()->create(['message' => 'Newer feedback', 'created_at' => now()]);

        $this->actingAs($admin)->getJson('/api/v1/admin/beta-feedback?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.0.user.id', $newer->user_id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonMissing(['id' => $older->id]);
    }
}
