<?php

namespace App\Services;

use App\Models\SermonCompanion;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SermonCompanionScriptureService
{
    public function __construct(private readonly BibleReaderService $reader) {}

    /** @return array<int, array{reference: string, translation: string, text: string}> */
    public function forCompanion(SermonCompanion $companion): array
    {
        return $this->verify(array_values(array_filter((array) ($companion->scripture_references ?? []), 'is_string')));
    }

    /** @param array<int, string> $references */
    public function verify(array $references): array
    {
        $verified = [];
        $translation = (string) config('bible.default_translation');

        foreach (array_unique($references) as $reference) {
            $parsed = $this->reader->parseReference($reference);
            if ($parsed === null) {
                continue;
            }

            try {
                $book = $this->reader->resolveBookId($translation, $parsed['book']);
                $passage = $this->reader->passage($translation, $book, $parsed['chapter'], $parsed['verse_start'], $parsed['verse_end']);
            } catch (InvalidArgumentException) {
                continue;
            }

            $reference = $passage['book']['name'].' '.$passage['chapter'].':'.$passage['verse_start'];
            if ($passage['verse_end'] !== $passage['verse_start']) {
                $reference .= '-'.$passage['verse_end'];
            }
            $verified[] = [
                'reference' => $reference,
                'translation' => $passage['translation']['abbreviation'],
                'text' => collect($passage['verses'])->map(fn (array $verse): string => $verse['number'].' '.$verse['text'])->implode("\n"),
            ];
        }

        if ($verified === []) {
            throw ValidationException::withMessages(['scripture_references' => 'At least one Scripture reference must be verified before generating companion content.']);
        }

        return $verified;
    }
}
