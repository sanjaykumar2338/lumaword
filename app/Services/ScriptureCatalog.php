<?php

namespace App\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class ScriptureCatalog
{
    /** @return array{reference: string, translation: string, text: string} */
    public function find(string $key): array
    {
        $passage = config("scripture.passages.{$key}");

        if (! is_array($passage)) {
            throw new InvalidArgumentException("Scripture passage [{$key}] is not defined in the catalog.");
        }

        return $passage;
    }

    /** @return Collection<string, array{key: string, reference: string, translation: string, book: string, chapter: int, verse_start: int, verse_end: int}> */
    public function options(): Collection
    {
        return collect(config('scripture.passages', []))
            ->map(function (array $passage, string $key): array {
                $metadata = $this->referenceMetadata($passage['reference']);

                return [
                    'key' => $key,
                    'reference' => $passage['reference'],
                    'translation' => $passage['translation'],
                    ...$metadata,
                ];
            });
    }

    /** @return array<string, string> */
    public function source(): array
    {
        return config('scripture.source');
    }

    /** @return array{book: string, chapter: int, verse_start: int, verse_end: int} */
    private function referenceMetadata(string $reference): array
    {
        $matched = preg_match(
            '/^(?<book>(?:[1-3] )?[A-Za-z]+(?: [A-Za-z]+)*) (?<chapter>\d+):(?<start>\d+)(?:-(?<end>\d+))?$/',
            $reference,
            $matches,
        );

        if ($matched !== 1) {
            throw new InvalidArgumentException("Scripture reference [{$reference}] cannot be used by the passage selector.");
        }

        return [
            'book' => $matches['book'],
            'chapter' => (int) $matches['chapter'],
            'verse_start' => (int) $matches['start'],
            'verse_end' => (int) (($matches['end'] ?? '') !== '' ? $matches['end'] : $matches['start']),
        ];
    }
}
