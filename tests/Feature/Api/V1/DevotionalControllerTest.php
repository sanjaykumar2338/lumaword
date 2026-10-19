<?php

namespace Tests\Feature\Api\V1;

use App\Contracts\DevotionalGenerator;
use App\Models\Devotional;
use App\Models\Sermon;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

class DevotionalControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_user_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/devotionals')->assertUnauthorized();
    }

    public function test_today_returns_exact_scripture_separately_from_editorial_reflection(): void
    {
        $this->travelTo('2026-10-19 09:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/devotionals/today')
            ->assertOk()
            ->assertJsonPath('data.translation', 'WEB')
            ->assertJsonPath('data.is_ai_generated', false)
            ->assertJsonStructure(['data' => [
                'title', 'scripture_reference', 'scripture_text', 'reflection', 'application', 'prayer',
            ]]);
    }

    public function test_valid_passage_generation_creates_saved_ai_devotional_and_returns_201(): void
    {
        $user = User::factory()->create();
        $generator = $this->mock(DevotionalGenerator::class);
        $generator->shouldReceive('generate')
            ->once()
            ->withArgs(fn (array $scripture, ?Sermon $sermon): bool => $scripture['reference'] === 'Psalm 23:1-6' && $sermon === null)
            ->andReturn($this->generatedContent());

        $response = $this->actingAs($user)->postJson('/api/v1/devotionals', [
            'scripture_key' => 'psalm-23',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Held by the Shepherd')
            ->assertJsonPath('data.scripture_reference', 'Psalm 23:1-6')
            ->assertJsonPath('data.is_ai_generated', true);
        $this->assertDatabaseHas('devotionals', [
            'user_id' => $user->id,
            'source_type' => 'passage',
            'scripture_key' => 'psalm-23',
        ]);
    }

    public function test_owned_sermon_can_ground_a_saved_devotional(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        $generator = $this->mock(DevotionalGenerator::class);
        $generator->shouldReceive('generate')
            ->once()
            ->withArgs(fn (array $scripture, ?Sermon $selected): bool => $selected?->is($sermon) === true)
            ->andReturn($this->generatedContent());

        $this->actingAs($user)->postJson('/api/v1/devotionals', [
            'scripture_key' => 'john-3-16-21',
            'sermon_id' => $sermon->id,
        ])->assertCreated()->assertJsonPath('data.sermon_id', $sermon->id);

        $this->assertDatabaseHas('devotionals', [
            'sermon_id' => $sermon->id,
            'source_type' => 'sermon',
        ]);
    }

    public function test_another_users_sermon_returns_422_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $sermon = Sermon::factory()->for($otherUser)->create();

        $this->actingAs($user)->postJson('/api/v1/devotionals', [
            'scripture_key' => 'psalm-23',
            'sermon_id' => $sermon->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('sermon_id');

        $this->assertDatabaseCount('devotionals', 0);
    }

    public function test_user_cannot_view_another_users_devotional(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $devotional = Devotional::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->getJson("/api/v1/devotionals/{$devotional->id}")
            ->assertForbidden();
    }

    public function test_ai_failure_returns_500_and_does_not_save_partial_devotional(): void
    {
        $user = User::factory()->create();
        $generator = $this->mock(DevotionalGenerator::class);
        $generator->shouldReceive('generate')->once()->andThrow(new RuntimeException('Provider unavailable'));

        $this->actingAs($user)
            ->postJson('/api/v1/devotionals', ['scripture_key' => 'psalm-23'])
            ->assertInternalServerError();

        $this->assertDatabaseCount('devotionals', 0);
    }

    public function test_second_generation_returns_429_when_daily_limit_is_reached(): void
    {
        config(['devotionals.daily_generation_per_user' => 1]);
        $user = User::factory()->create();
        $generator = $this->mock(DevotionalGenerator::class);
        $generator->shouldReceive('generate')->once()->andReturn($this->generatedContent());

        $this->actingAs($user)
            ->postJson('/api/v1/devotionals', ['scripture_key' => 'psalm-23'])
            ->assertCreated();
        $this->actingAs($user)
            ->postJson('/api/v1/devotionals', ['scripture_key' => 'psalm-23'])
            ->assertTooManyRequests();

        RateLimiter::clear((string) $user->id);
    }

    /** @return array<string, string> */
    private function generatedContent(): array
    {
        return [
            'title' => 'Held by the Shepherd',
            'reflection' => 'A reflection grounded in the selected passage.',
            'application' => 'Entrust one concern to God today.',
            'prayer' => 'Lord, lead me in your faithful care. Amen.',
            'provider' => 'openai',
            'model' => 'test-model',
            'prompt_version' => 'devotional-v1',
        ];
    }
}
