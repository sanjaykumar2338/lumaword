<?php

namespace Tests\Feature\Api\V1;

use App\BibleStudyDepth;
use App\Contracts\BibleStudyGenerator;
use App\Exceptions\BibleStudyGenerationException;
use App\Models\BibleStudy;
use App\Models\User;
use Database\Factories\BibleStudyFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BibleStudyControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_user_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/bible-studies')->assertUnauthorized();
        $this->postJson('/api/v1/bible-studies')->assertUnauthorized();
    }

    public function test_valid_passage_and_depth_generate_and_save_private_study_with_201(): void
    {
        $user = User::factory()->create();
        $generator = $this->mock(BibleStudyGenerator::class);
        $generator->shouldReceive('generate')
            ->once()
            ->withArgs(fn (array $scripture, BibleStudyDepth $depth): bool => $scripture['reference'] === 'Psalm 23:1-6'
                && $depth === BibleStudyDepth::GoDeeper)
            ->andReturn($this->generatedStudy());

        $response = $this->actingAs($user)->postJson('/api/v1/bible-studies', [
            'scripture_key' => 'psalm-23',
            'depth' => 'go_deeper',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'The Shepherd Who Stays Near')
            ->assertJsonPath('data.depth', 'go_deeper')
            ->assertJsonPath('data.scripture_reference', 'Psalm 23:1-6')
            ->assertJsonPath('meta.reused', false);
        $this->assertDatabaseHas('bible_studies', [
            'user_id' => $user->id,
            'scripture_key' => 'psalm-23',
            'depth' => 'go_deeper',
            'personal_notes' => null,
        ]);
    }

    public function test_repeated_request_reopens_saved_study_without_another_ai_call(): void
    {
        $user = User::factory()->create();
        $generator = $this->mock(BibleStudyGenerator::class);
        $generator->shouldReceive('generate')->once()->andReturn($this->generatedStudy());

        $first = $this->actingAs($user)->postJson('/api/v1/bible-studies', [
            'scripture_key' => 'psalm-23',
            'depth' => 'quick_read',
        ])->assertCreated();
        $second = $this->actingAs($user)->postJson('/api/v1/bible-studies', [
            'scripture_key' => 'psalm-23',
            'depth' => 'quick_read',
        ]);

        $second
            ->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'))
            ->assertJsonPath('meta.reused', true);
        $this->assertDatabaseCount('bible_studies', 1);
    }

    public function test_rejects_unavailable_passage_and_invalid_depth_with_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/bible-studies', [
            'scripture_key' => 'made-up-2-4-9',
            'depth' => 'exhaustive',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['scripture_key', 'depth']);

        $this->assertDatabaseCount('bible_studies', 0);
    }

    public function test_requires_passage_and_depth_with_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/bible-studies')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scripture_key', 'depth']);

        $this->assertDatabaseCount('bible_studies', 0);
    }

    public function test_owner_can_reopen_study_and_save_personal_notes_separately(): void
    {
        $user = User::factory()->create();
        $study = BibleStudy::factory()->for($user)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/bible-studies/{$study->id}")
            ->assertOk()
            ->assertJsonPath('data.content.overview', $study->content['overview']);

        $this->actingAs($user)
            ->patchJson("/api/v1/bible-studies/{$study->id}", [
                'personal_notes' => 'I want to revisit the change in imagery in verse 5.',
            ])->assertOk()
            ->assertJsonPath('data.personal_notes', 'I want to revisit the change in imagery in verse 5.')
            ->assertJsonPath('data.content.overview', $study->content['overview']);

        $this->assertDatabaseHas('bible_studies', [
            'id' => $study->id,
            'personal_notes' => 'I want to revisit the change in imagery in verse 5.',
        ]);
    }

    public function test_another_user_cannot_view_or_change_private_study(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $study = BibleStudy::factory()->for($owner)->create(['personal_notes' => 'Private reflection']);

        $this->actingAs($otherUser)
            ->getJson("/api/v1/bible-studies/{$study->id}")
            ->assertForbidden();
        $this->actingAs($otherUser)
            ->patchJson("/api/v1/bible-studies/{$study->id}", ['personal_notes' => 'Changed'])
            ->assertForbidden();

        $this->assertDatabaseHas('bible_studies', [
            'id' => $study->id,
            'personal_notes' => 'Private reflection',
        ]);
    }

    public function test_ai_failure_returns_503_and_does_not_save_partial_study(): void
    {
        $user = User::factory()->create();
        $generator = $this->mock(BibleStudyGenerator::class);
        $generator->shouldReceive('generate')->once()->andThrow(
            new BibleStudyGenerationException('Luma could not generate this study right now. No partial study was saved.'),
        );

        $this->actingAs($user)->postJson('/api/v1/bible-studies', [
            'scripture_key' => 'psalm-23',
            'depth' => 'study_in_depth',
        ])->assertServiceUnavailable()
            ->assertJsonPath('message', 'Luma could not generate this study right now. No partial study was saved.');

        $this->assertDatabaseCount('bible_studies', 0);
    }

    public function test_generation_returns_429_when_daily_limit_is_reached(): void
    {
        config(['bible_studies.daily_generation_per_user' => 1]);
        $user = User::factory()->create();
        $generator = $this->mock(BibleStudyGenerator::class);
        $generator->shouldReceive('generate')->once()->andReturn($this->generatedStudy());

        $this->actingAs($user)->postJson('/api/v1/bible-studies', [
            'scripture_key' => 'psalm-23',
            'depth' => 'quick_read',
        ])->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/bible-studies', [
            'scripture_key' => 'john-3-16-21',
            'depth' => 'quick_read',
        ])->assertTooManyRequests();

        RateLimiter::clear((string) $user->id);
    }

    /** @return array{content: array<string, mixed>, provider: string, model: string, prompt_version: string, response_id: string} */
    private function generatedStudy(): array
    {
        return [
            'content' => BibleStudyFactory::content(),
            'provider' => 'openai',
            'model' => 'test-model',
            'prompt_version' => 'bible-study-v1',
            'response_id' => 'resp_test',
        ];
    }
}
