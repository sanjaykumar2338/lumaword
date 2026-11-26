<?php

namespace Tests\Feature\Api\V1;

use App\Contracts\DevotionalGenerator;
use App\Models\Sermon;
use App\Models\SermonNote;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SermonDevotionalControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_another_user_cannot_generate_a_sermon_devotional(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $sermon = Sermon::factory()->for($owner)->completed()->create();

        $this->actingAs($otherUser)->postJson("/api/v1/sermons/{$sermon->id}/devotional")
            ->assertForbidden();
    }

    public function test_completed_owned_sermon_creates_a_grounded_devotional(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->completed()->create();
        SermonNote::factory()->for($sermon)->create(['scripture_references' => ['Nehemiah 1:1-3']]);
        $generator = $this->mock(DevotionalGenerator::class);
        $generator->shouldReceive('generate')->once()->withArgs(fn (array $scripture, ?Sermon $source): bool => $source?->is($sermon)
            && $scripture['reference'] === 'Nehemiah 1:1-3')->andReturn([
                'title' => 'A Faithful Prayer',
                'reflection' => 'Reflect on prayerful faithfulness in a difficult season.',
                'application' => 'Set aside ten minutes to pray for your community.',
                'prayer' => 'Lord, teach us to seek you faithfully. Amen.',
                'provider' => 'test',
                'model' => 'test-model',
                'prompt_version' => 'test',
            ]);

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/devotional")
            ->assertCreated()
            ->assertJsonPath('data.sermon_id', $sermon->id)
            ->assertJsonPath('data.scripture_reference', 'Nehemiah 1:1-3');

        $this->assertDatabaseHas('devotionals', ['user_id' => $user->id, 'sermon_id' => $sermon->id]);
    }

    public function test_repeated_sermon_devotional_request_reuses_history_without_another_generation(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->completed()->create();
        SermonNote::factory()->for($sermon)->create(['scripture_references' => ['John 3:16']]);
        $user->devotionals()->create([
            'sermon_id' => $sermon->id, 'source_type' => 'sermon', 'title' => 'Existing',
            'scripture_key' => 'bible:WEB:JHN:3:16:16', 'scripture_reference' => 'John 3:16',
            'translation' => 'WEB', 'scripture_text' => 'For God so loved the world.',
            'reflection' => 'Existing reflection.', 'application' => 'Existing application.',
            'prayer' => 'Existing prayer.', 'ai_provider' => 'test', 'ai_model' => 'test',
            'prompt_version' => 'test', 'generated_at' => now(),
        ]);

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/devotional")
            ->assertOk()->assertJsonPath('meta.reused', true);
        $this->assertDatabaseCount('devotionals', 1);
    }
}
