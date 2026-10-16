<?php

namespace App\Services;

use Illuminate\Support\Str;

class SermonTranscriptContextSelector
{
    /**
     * @return array{chunks: array<int, array{section: int, text: string}>, low_confidence: bool}
     */
    public function select(string $transcript, string $question): array
    {
        $chunks = $this->chunks($transcript);
        $terms = $this->terms($question);

        $scored = collect($chunks)->map(function (string $chunk, int $index) use ($terms, $question): array {
            $normalizedChunk = Str::lower($chunk);
            $score = collect($terms)->sum(fn (string $term): int => substr_count($normalizedChunk, $term));

            if (Str::contains($normalizedChunk, Str::lower(trim($question)))) {
                $score += 10;
            }

            return ['section' => $index + 1, 'text' => $chunk, 'score' => $score];
        });

        $hasRelevantMatch = $scored->contains(fn (array $chunk): bool => $chunk['score'] > 0);
        $selected = $hasRelevantMatch
            ? $scored->sortByDesc('score')->take((int) config('sermons.qa.maximum_context_chunks'))
            : $scored->take(min(3, (int) config('sermons.qa.maximum_context_chunks')));

        $remainingCharacters = (int) config('sermons.qa.maximum_context_characters');
        $bounded = [];

        foreach ($selected as $chunk) {
            if ($remainingCharacters <= 0) {
                break;
            }

            $text = mb_substr($chunk['text'], 0, $remainingCharacters);
            $bounded[] = ['section' => $chunk['section'], 'text' => $text];
            $remainingCharacters -= mb_strlen($text);
        }

        usort($bounded, fn (array $left, array $right): int => $left['section'] <=> $right['section']);

        return ['chunks' => $bounded, 'low_confidence' => ! $hasRelevantMatch];
    }

    /** @return array<int, string> */
    private function chunks(string $transcript): array
    {
        $paragraphs = preg_split('/\R{2,}/u', trim($transcript)) ?: [];
        $chunks = [];
        $current = '';
        $targetLength = (int) config('sermons.qa.context_chunk_characters');

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            foreach (mb_str_split($paragraph, $targetLength) as $part) {
                if ($current !== '' && mb_strlen($current."\n\n".$part) > $targetLength) {
                    $chunks[] = $current;
                    $current = '';
                }

                $current = $current === '' ? $part : $current."\n\n".$part;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /** @return array<int, string> */
    private function terms(string $question): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', Str::lower($question), $matches);
        $stopWords = ['about', 'could', 'did', 'does', 'from', 'have', 'into', 'main', 'said', 'sermon', 'that', 'the', 'their', 'this', 'was', 'what', 'when', 'where', 'which', 'with', 'would'];

        return collect($matches[0] ?? [])
            ->filter(fn (string $term): bool => mb_strlen($term) >= 3 && ! in_array($term, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }
}
