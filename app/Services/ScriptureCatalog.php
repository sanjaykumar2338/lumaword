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

    /** @return Collection<string, array{key: string, reference: string, translation: string}> */
    public function options(): Collection
    {
        return collect(config('scripture.passages', []))
            ->map(fn (array $passage, string $key): array => [
                'key' => $key,
                'reference' => $passage['reference'],
                'translation' => $passage['translation'],
            ]);
    }

    /** @return array<string, string> */
    public function source(): array
    {
        return config('scripture.source');
    }
}
