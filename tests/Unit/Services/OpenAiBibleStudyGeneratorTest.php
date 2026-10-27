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
        $this->expectExceptionMessage('Luma could not generate this study right now. No partial study was saved.');

        (new OpenAiBibleStudyGenerator(new BibleStudyContentSchema))->generate(
            ['reference' => 'Psalm 23:1-6', 'translation' => 'WEB', 'text' => 'Trusted passage text.'],
            BibleStudyDepth::QuickRead,
        );
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
