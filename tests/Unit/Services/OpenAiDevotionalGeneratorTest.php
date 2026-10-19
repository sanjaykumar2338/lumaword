<?php

namespace Tests\Unit\Services;

use App\Services\OpenAiDevotionalGenerator;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiDevotionalGeneratorTest extends TestCase
{
    public function test_returns_structured_reflection_without_generating_scripture(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [['content' => [[
                    'type' => 'output_text',
                    'text' => json_encode([
                        'title' => 'Held by the Shepherd',
                        'reflection' => 'A grounded reflection that is long enough for the schema.',
                        'application' => 'Entrust one concern to God today.',
                        'prayer' => 'Lord, lead me in your faithful care. Amen.',
                    ], JSON_THROW_ON_ERROR),
                ]]]],
            ]),
        ]);

        $result = (new OpenAiDevotionalGenerator)->generate(config('scripture.passages.psalm-23'));

        $this->assertSame('Held by the Shepherd', $result['title']);
        $this->assertArrayNotHasKey('scripture_text', $result);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['store'] === false
            && ! array_key_exists('scripture_text', $request['text']['format']['schema']['properties']));
    }

    public function test_provider_failure_throws_without_fallback_content(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Unavailable']], 500),
        ]);
        $this->expectException(RequestException::class);

        (new OpenAiDevotionalGenerator)->generate(config('scripture.passages.psalm-23'));
    }
}
