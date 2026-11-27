<?php

namespace App;

use App\Models\SermonCompanionAttachment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SermonCompanionAttachmentProcessor
{
    /** @return array{extracted_text: string, is_unclear: bool, language: ?string} */
    public function process(SermonCompanionAttachment $attachment): array
    {
        if (! Storage::disk('local')->exists($attachment->path)) {
            throw new RuntimeException('The private attachment is unavailable.');
        }

        return $attachment->kind === 'image' ? $this->extractImageText($attachment) : $this->transcribeAudio($attachment);
    }

    /** @return array{extracted_text: string, is_unclear: bool, language: ?string} */
    private function extractImageText(SermonCompanionAttachment $attachment): array
    {
        $dataUrl = 'data:'.$attachment->mime_type.';base64,'.base64_encode(Storage::disk('local')->get($attachment->path));
        $response = $this->client()->post('/responses', ['model' => config('services.openai.vision_model'), 'store' => false, 'input' => [['role' => 'developer', 'content' => [['type' => 'input_text', 'text' => 'Transcribe only visible handwritten or printed notes. Never guess unreadable text. Return JSON with extracted_text, is_unclear, and language.']]], ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => 'Extract the note text faithfully.'], ['type' => 'input_image', 'image_url' => $dataUrl]]]], 'text' => ['format' => ['type' => 'json_schema', 'name' => 'note_extraction', 'strict' => true, 'schema' => ['type' => 'object', 'additionalProperties' => false, 'properties' => ['extracted_text' => ['type' => 'string'], 'is_unclear' => ['type' => 'boolean'], 'language' => ['type' => ['string', 'null']]], 'required' => ['extracted_text', 'is_unclear', 'language']]]]])->throw();
        $text = collect($response->json('output', []))->flatMap(fn (array $item): array => $item['content'] ?? [])->firstWhere('type', 'output_text')['text'] ?? null;
        $decoded = is_string($text) ? json_decode($text, true) : null;
        if (! is_array($decoded) || ! is_string($decoded['extracted_text'] ?? null)) {
            throw new RuntimeException('OpenAI returned an invalid note extraction.');
        }

        return ['extracted_text' => trim($decoded['extracted_text']), 'is_unclear' => (bool) ($decoded['is_unclear'] ?? false), 'language' => is_string($decoded['language'] ?? null) ? $decoded['language'] : null];
    }

    /** @return array{extracted_text: string, is_unclear: bool, language: ?string} */
    private function transcribeAudio(SermonCompanionAttachment $attachment): array
    {
        $stream = fopen(Storage::disk('local')->path($attachment->path), 'rb');
        if ($stream === false) {
            throw new RuntimeException('The private audio file could not be opened.');
        }
        try {
            $response = $this->client()->attach('file', $stream, $attachment->original_name, ['Content-Type' => $attachment->mime_type])->post('/audio/transcriptions', ['model' => config('services.openai.transcription_model'), 'response_format' => 'json'])->throw();
        } finally {
            fclose($stream);
        }
        $text = $response->json('text');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('OpenAI did not return a transcript.');
        }

        return ['extracted_text' => trim($text), 'is_unclear' => false, 'language' => is_string($response->json('language')) ? $response->json('language') : null];
    }

    private function client(): PendingRequest
    {
        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('OpenAI is not configured.');
        }

        return Http::baseUrl((string) config('services.openai.base_url'))->acceptJson()->withToken($key)->connectTimeout((int) config('services.openai.connect_timeout'))->timeout((int) config('services.openai.timeout'));
    }
}
