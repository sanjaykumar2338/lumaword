<?php

namespace Tests\Feature\Services;

use App\Models\Sermon;
use App\Services\OpenAiSermonService;
use App\Services\SermonNotesSchema;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiSermonServiceTest extends TestCase
{
    public function test_structured_response_is_validated_and_returned(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_123',
                'output' => [[
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode($this->notes(), JSON_THROW_ON_ERROR),
                    ]],
                ]],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 80],
            ]),
        ]);
        $sermon = new Sermon([
            'title' => 'Prayer',
            'source_type' => 'transcript',
            'processing_status' => 'analyzing',
            'transcript' => str_repeat('The sermon teaches faithful prayer. ', 8),
            'transcript_source' => 'pasted',
        ]);
        $sermon->id = 42;

        $result = (new OpenAiSermonService(new SermonNotesSchema))->analyze($sermon);

        $this->assertSame('resp_123', $result['response_id']);
        $this->assertSame('Prayer and trust', $result['notes']['main_idea']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['text']['format']['strict'] === true
            && $request['store'] === false);
    }

    public function test_failed_api_request_throws_and_returns_no_fake_result(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['https://api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Unavailable']], 500)]);
        $sermon = new Sermon([
            'title' => 'Prayer',
            'source_type' => 'transcript',
            'processing_status' => 'analyzing',
            'transcript' => str_repeat('The sermon teaches faithful prayer. ', 8),
            'transcript_source' => 'pasted',
        ]);
        $sermon->id = 43;

        $this->expectException(RequestException::class);

        (new OpenAiSermonService(new SermonNotesSchema))->analyze($sermon);
    }

    /** @return array<string, mixed> */
    private function notes(): array
    {
        return [
            'title' => 'Faithful Prayer',
            'main_idea' => 'Prayer and trust',
            'summary' => 'The sermon connects prayer with trust in God.',
            'outline' => [['heading' => 'Prayer', 'details' => 'Pray with confidence in God.']],
            'key_points' => ['Prayer expresses dependence.'],
            'scripture_references' => [],
            'themes' => ['prayer'],
            'applications' => ['Pray each morning.'],
            'discussion_questions' => ['How can prayer shape trust?'],
        ];
    }
}
