<?php

namespace Tests\Unit\Services;

use App\BibleStudyDepth;
use App\Exceptions\BibleStudyGenerationException;
use App\Services\BibleStudyContentSchema;
use App\Services\OpenAiBibleStudyGenerator;
use Database\Factories\BibleStudyFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiBibleStudyGeneratorTest extends TestCase
{
    public function test_returns_validated_structured_study_grounded_in_selected_passage_and_depth(): void
    {
        $this->configureOpenAi();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_study',
                'output' => [['content' => [[
                    'type' => 'output_text',
                    'text' => json_encode(BibleStudyFactory::content(), JSON_THROW_ON_ERROR),
                ]]]],
            ]),
        ]);

        $result = (new OpenAiBibleStudyGenerator(new BibleStudyContentSchema))->generate(
            ['reference' => 'Psalm 23:1-6', 'translation' => 'WEB', 'text' => 'Trusted passage text.'],
            BibleStudyDepth::StudyInDepth,
        );

        $this->assertSame('The Shepherd Who Stays Near', $result['content']['title']);
        $this->assertSame('resp_study', $result['response_id']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['store'] === false
            && str_contains($request['input'][0]['content'][0]['text'], 'Study in Depth')
            && str_contains($request['input'][1]['content'][0]['text'], 'Trusted passage text.')
            && ! array_key_exists('scripture_text', $request['text']['format']['schema']['properties']));
    }

    public function test_provider_failure_becomes_safe_generation_exception(): void
    {
        $this->configureOpenAi();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Provider details']], 500),
        ]);
        $this->expectException(BibleStudyGenerationException::class);
        $this->expectExceptionMessage('The AI provider is temporarily unavailable. Please try again.');

        (new OpenAiBibleStudyGenerator(new BibleStudyContentSchema))->generate(
            ['reference' => 'Psalm 23:1-6', 'translation' => 'WEB', 'text' => 'Trusted passage text.'],
            BibleStudyDepth::QuickRead,
        );
    }

    public function test_quick_read_and_go_deeper_use_the_selected_depth(): void
    {
        $this->configureOpenAi();
        Http::preventStrayRequests();
        $content = json_encode(BibleStudyFactory::content());
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::sequence()
                ->push(['id' => 'quick', 'output' => [['content' => [['type' => 'output_text', 'text' => $content]]]]])
                ->push(['id' => 'deep', 'output' => [['content' => [['type' => 'output_text', 'text' => $content]]]]]),
        ]);

        $generator = new OpenAiBibleStudyGenerator(new BibleStudyContentSchema);
        $scripture = ['reference' => 'John 3:16', 'translation' => 'WEB', 'text' => 'Trusted passage text.'];
        $generator->generate($scripture, BibleStudyDepth::QuickRead);
        $generator->generate($scripture, BibleStudyDepth::GoDeeper);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => str_contains($request['input'][0]['content'][0]['text'], 'Quick Read'));
        Http::assertSent(fn ($request): bool => str_contains($request['input'][0]['content'][0]['text'], 'Go Deeper'));
    }

    public function test_invalid_structured_response_is_classified_and_logged_without_source_text(): void
    {
        $this->configureOpenAi();
        Http::fake(['https://api.openai.com/v1/responses' => Http::response([
            'id' => 'invalid',
            'output' => [['content' => [['type' => 'output_text', 'text' => '{\"unexpected\":true}']]]],
        ])]);
        try {
            (new OpenAiBibleStudyGenerator(new BibleStudyContentSchema))->generate(
                ['reference' => 'John 3:16', 'translation' => 'WEB', 'text' => 'PRIVATE TRANSCRIPT TEXT'],
                BibleStudyDepth::QuickRead,
            );
            $this->fail('Expected a generation exception.');
        } catch (BibleStudyGenerationException $exception) {
            $this->assertSame(BibleStudyGenerationException::VALIDATION, $exception->category);
        }
    }

    private function configureOpenAi(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.connect_timeout' => 1,
            'services.openai.timeout' => 2,
            'bible_studies.maximum_output_tokens' => 7000,
            'bible_studies.prompt_version' => 'bible-study-v1',
        ]);
    }
}
