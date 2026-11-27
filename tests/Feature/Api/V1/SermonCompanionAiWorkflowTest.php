<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\GenerateSermonCompanionDevotional;
use App\Jobs\GenerateSermonCompanionRecap;
use App\Models\AiUsageReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SermonCompanionAiWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_recap_queues_and_persists_openai_recap_and_verified_memory_card(): void
    {
        config(['services.openai.key' => 'test-key']);
        Queue::fake([GenerateSermonCompanionRecap::class]);
        $user = User::factory()->create();
        $companion = $user->sermonCompanions()->create(['title' => 'Grace', 'status' => 'ready', 'confirmed_notes' => 'Grace changes how we live.', 'scripture_references' => ['John 3:16']]);
        $this->actingAs($user)->postJson("/api/v1/sermon-companions/{$companion->id}/recap", [], ['Idempotency-Key' => 'recap-1'])->assertStatus(202)->assertJsonPath('data.status', 'generating_recap');
        $reservation = AiUsageReservation::query()->sole();
        Queue::assertPushed(GenerateSermonCompanionRecap::class);
        Http::fakeSequence()->push($this->openAiResponse(['main_idea' => 'Grace is received and lived out.', 'summary' => 'A concise recap.', 'supporting_points' => ['Grace changes our response.'], 'key_takeaways' => ['Receive grace.'], 'applications' => ['Practice grace.'], 'reflection_questions' => ['Where can I show grace?']]))->push($this->openAiResponse(['cards' => [['reference' => 'John 3:16', 'blank_words' => ['For']]]]));
        (new GenerateSermonCompanionRecap($companion->id, $reservation->id))->handle(app('App\Services\OpenAiSermonCompanionGenerator'), app('App\Services\SermonCompanionScriptureService'), app('App\Services\AiUsageService'));
        $this->assertSame('completed', $companion->refresh()->status);
        $this->assertSame('Grace is received and lived out.', $companion->recap['main_idea']);
        $this->assertDatabaseHas('sermon_companion_memory_cards', ['sermon_companion_id' => $companion->id, 'reference' => 'John 3:16']);
        $this->assertSame('completed', $reservation->refresh()->status);
    }

    public function test_devotional_job_generates_exactly_five_days_with_verified_bible_text(): void
    {
        config(['services.openai.key' => 'test-key']);
        $user = User::factory()->create();
        $companion = $user->sermonCompanions()->create(['title' => 'Hope', 'status' => 'ready', 'confirmed_notes' => 'Hope in Christ.', 'scripture_references' => ['John 3:16']]);
        $plan = $companion->devotionalPlans()->create(['user_id' => $user->id, 'days_count' => 5, 'status' => 'queued']);
        $reservation = AiUsageReservation::factory()->for($user)->create(['feature' => 'sermon_companion', 'idempotency_key' => 'devotional-5']);
        $days = array_map(fn (int $day): array => ['title' => "Day {$day}", 'theme' => 'Hope', 'reflection' => 'A grounded reflection on hope.', 'reflection_question' => 'How will you practice hope?', 'practical_application' => 'Encourage someone today.', 'prayer' => 'God, strengthen my hope.'], range(1, 5));
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->openAiResponse(['days' => $days]))]);
        (new GenerateSermonCompanionDevotional($plan->id, $reservation->id))->handle(app('App\Services\OpenAiSermonCompanionGenerator'), app('App\Services\SermonCompanionScriptureService'), app('App\Services\AiUsageService'));
        $expectedText = app('App\Services\SermonCompanionScriptureService')->forCompanion($companion)[0]['text'];
        $this->assertSame('completed', $plan->refresh()->status);
        $this->assertCount(5, $plan->days);
        $this->assertSame($expectedText, $plan->days->first()->scripture_text);
        $this->assertSame('completed', $reservation->refresh()->status);
    }

    public function test_devotional_job_generates_exactly_seven_days(): void
    {
        config(['services.openai.key' => 'test-key']);
        $user = User::factory()->create();
        $companion = $user->sermonCompanions()->create(['title' => 'Trust', 'status' => 'ready', 'scripture_references' => ['John 3:16']]);
        $plan = $companion->devotionalPlans()->create(['user_id' => $user->id, 'days_count' => 7, 'status' => 'queued']);
        $reservation = AiUsageReservation::factory()->for($user)->create(['feature' => 'sermon_companion', 'idempotency_key' => 'devotional-7']);
        $days = array_map(fn (int $day): array => ['title' => "Day {$day}", 'theme' => 'Trust', 'reflection' => 'A grounded reflection on trust.', 'reflection_question' => 'Where can you practice trust?', 'practical_application' => 'Take one faithful step.', 'prayer' => 'God, strengthen my trust.'], range(1, 7));
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->openAiResponse(['days' => $days]))]);
        (new GenerateSermonCompanionDevotional($plan->id, $reservation->id))->handle(app('App\Services\OpenAiSermonCompanionGenerator'), app('App\Services\SermonCompanionScriptureService'), app('App\Services\AiUsageService'));
        $this->assertCount(7, $plan->refresh()->days);
    }

    public function test_invalid_scripture_reference_is_rejected_before_ai_is_queued(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $companion = $user->sermonCompanions()->create(['title' => 'Invalid', 'status' => 'ready', 'scripture_references' => ['NotABook 99:99']]);
        $this->actingAs($user)->postJson("/api/v1/sermon-companions/{$companion->id}/devotional-plans", ['days_count' => 7])->assertUnprocessable()->assertJsonValidationErrors('scripture_references');
        Queue::assertNothingPushed();
    }

    /** @param array<string, mixed> $content */
    private function openAiResponse(array $content): array
    {
        return ['id' => 'resp-companion', 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($content, JSON_THROW_ON_ERROR)]]]], 'usage' => ['input_tokens' => 10, 'output_tokens' => 20]];
    }
}
