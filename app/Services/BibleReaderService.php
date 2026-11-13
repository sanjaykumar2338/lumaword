<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BibleReaderService
{
    /** @return array<int, array<string, mixed>> */
    public function translations(): array
    {
        return collect(config('bible.translations', []))
            ->map(fn (array $translation, string $abbreviation): array => [
                'abbreviation' => $abbreviation,
                'name' => $translation['name'],
                'edition' => $translation['edition'],
                'license' => $translation['license'],
                'availability' => $translation['availability'],
                'source_url' => $translation['source_url'],
                'copyright_url' => $translation['copyright_url'],
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{id: string, name: string, chapter_count: int}> */
    public function books(string $translation): array
    {
        return collect($this->catalog($translation)['books'])
            ->map(fn (array $book): array => [
                'id' => $book['id'],
                'name' => $book['name'],
                'chapter_count' => count($book['chapters']),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{number: int, verse_count: int}> */
    public function chapters(string $translation, string $bookId): array
    {
        $book = $this->book($translation, $bookId);

        return collect($book['chapters'])
            ->map(fn (array $verses, string $number): array => [
                'number' => (int) $number,
                'verse_count' => count($verses),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function passage(string $translation, string $bookId, int $chapter, ?int $verseStart = null, ?int $verseEnd = null): array
    {
        $book = $this->book($translation, $bookId);
        $chapters = $book['chapters'];
        $chapterKey = (string) $chapter;

        if (! isset($chapters[$chapterKey])) {
            throw new InvalidArgumentException('The requested chapter is not available.');
        }

        $verses = $chapters[$chapterKey];
        $verseStart ??= (int) array_key_first($verses);
        $verseEnd ??= (int) array_key_last($verses);

        if ($verseStart < 1 || $verseEnd < $verseStart) {
            throw new InvalidArgumentException('The requested verse range is invalid.');
        }

        $selected = collect($verses)
            ->filter(fn (string $text, string $verse): bool => (int) $verse >= $verseStart && (int) $verse <= $verseEnd)
            ->map(fn (string $text, string $verse): array => [
                'number' => (int) $verse,
                'text' => $text,
                'reference' => "{$book['name']} {$chapter}:{$verse}",
            ])
            ->values()
            ->all();

        if ($selected === []) {
            throw new InvalidArgumentException('The requested verse range is not available.');
        }

        return [
            'translation' => $this->translation($translation),
            'book' => ['id' => $book['id'], 'name' => $book['name']],
            'chapter' => $chapter,
            'verse_start' => $selected[0]['number'],
            'verse_end' => $selected[array_key_last($selected)]['number'],
            'verses' => $selected,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $translation, string $query, int $perPage = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $reference = $this->referenceQuery($translation, $query);
        if ($reference !== null) {
            $passage = $this->passage($translation, $reference['book'], $reference['chapter'], $reference['verse_start'], $reference['verse_end']);

            return collect($passage['verses'])
                ->map(fn (array $verse): array => [
                    ...$verse,
                    'translation' => $passage['translation'],
                    'book' => $passage['book'],
                    'chapter' => $passage['chapter'],
                ])
                ->all();
        }

        $needle = Str::lower($query);
        $results = [];

        foreach ($this->catalog($translation)['books'] as $book) {
            foreach ($book['chapters'] as $chapter => $verses) {
                foreach ($verses as $verse => $text) {
                    if (Str::contains(Str::lower($text), $needle)) {
                        $results[] = [
                            'number' => (int) $verse,
                            'text' => $text,
                            'reference' => "{$book['name']} {$chapter}:{$verse}",
                            'translation' => $this->translation($translation),
                            'book' => ['id' => $book['id'], 'name' => $book['name']],
                            'chapter' => (int) $chapter,
                        ];

                        if (count($results) >= $perPage) {
                            return $results;
                        }
                    }
                }
            }
        }

        return $results;
    }

    /** @return array{book: string, chapter: int, verse_start: int, verse_end: int}|null */
    public function parseReference(string $reference): ?array
    {
        $reference = str_replace(['–', '—'], '-', trim($reference));
        if (preg_match('/^(?<book>.+?)\s+(?<chapter>\d+)(?::(?<start>\d+)(?:-(?<end>\d+))?)?$/', $reference, $matches) !== 1) {
            return null;
        }

        $book = collect(config('bible.book_aliases', []))->get(Str::lower($matches['book']), $matches['book']);

        return [
            'book' => (string) $book,
            'chapter' => (int) $matches['chapter'],
            'verse_start' => (int) ($matches['start'] ?? 1),
            'verse_end' => (int) ($matches['end'] ?? $matches['start'] ?? 999),
        ];
    }

    /** @return array<string, mixed> */
    private function catalog(string $translation): array
    {
        return Cache::rememberForever("bible.catalog.{$this->normalizeTranslation($translation)}", function () use ($translation): array {
            $config = config('bible.translations.'.$this->normalizeTranslation($translation));
            if (! is_array($config) || ! is_file($config['data_file'])) {
                throw new InvalidArgumentException('The requested translation is not available.');
            }

            $catalog = json_decode((string) file_get_contents($config['data_file']), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($catalog)) {
                throw new InvalidArgumentException('The requested translation could not be loaded.');
            }

            return $catalog;
        });
    }

    /** @return array<string, mixed> */
    private function book(string $translation, string $bookId): array
    {
        $book = collect($this->catalog($translation)['books'])->firstWhere('id', Str::upper($bookId));
        if (! is_array($book)) {
            throw new InvalidArgumentException('The requested Bible book is not available.');
        }

        return $book;
    }

    /** @return array<string, mixed> */
    private function translation(string $translation): array
    {
        $normalized = $this->normalizeTranslation($translation);
        $config = config('bible.translations.'.$normalized);
        if (! is_array($config)) {
            throw new InvalidArgumentException('The requested translation is not available.');
        }

        return [
            'abbreviation' => $normalized,
            'name' => $config['name'],
            'edition' => $config['edition'],
            'license' => $config['license'],
            'source_url' => $config['source_url'],
            'copyright_url' => $config['copyright_url'],
        ];
    }

    private function normalizeTranslation(string $translation): string
    {
        $normalized = Str::upper($translation);
        if (! array_key_exists($normalized, config('bible.translations', []))) {
            throw new InvalidArgumentException('The requested translation is not available.');
        }

        return $normalized;
    }

    /** @return array{book: string, chapter: int, verse_start: int, verse_end: int}|null */
    private function referenceQuery(string $translation, string $query): ?array
    {
        $reference = $this->parseReference($query);
        if ($reference === null) {
            return null;
        }

        $book = collect($this->books($translation))->first(fn (array $book): bool => Str::lower($book['name']) === Str::lower($reference['book']) || $book['id'] === Str::upper($reference['book']));
        if (! is_array($book)) {
            return null;
        }

        $reference['book'] = $book['id'];

        return $reference;
    }
}
