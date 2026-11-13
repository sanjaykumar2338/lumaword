<?php

namespace App\Services;

use App\BibleStudyDepth;
use App\Contracts\BibleStudyGenerator;
use App\Exceptions\BibleStudyGenerationException;
use App\Models\BibleStudy;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class BibleStudyCreator
{
    public function __construct(
        private readonly ScriptureCatalog $scriptures,
        private readonly BibleReaderService $reader,
        private readonly BibleStudyGenerator $generator,
        private readonly AiUsageService $usage,
    ) {}

    /** @return array{study: BibleStudy, reused: bool} */
    public function create(User $user, ?string $scriptureKey, ?array $readerReference, BibleStudyDepth $depth): array
    {
        $scripture = $scriptureKey !== null
            ? $this->scriptures->find($scriptureKey)
            : $this->readerPassage($readerReference ?? []);
        $studyKey = $scriptureKey ?? $this->readerKey($readerReference ?? []);
        $requestHash = hash('sha256', implode('|', [
            $studyKey,
            $depth->value,
            (string) config('bible_studies.prompt_version'),
        ]));
        $existing = $user->bibleStudies()->where('request_hash', $requestHash)->first();

        if ($existing instanceof BibleStudy) {
            return ['study' => $existing, 'reused' => true];
        }

        $lock = Cache::lock(
            "bible-study-generation:{$user->id}:{$requestHash}",
            (int) config('bible_studies.lock_seconds'),
        );

        if (! $lock->get()) {
            throw new BibleStudyGenerationException(
                'This study is already being generated. Please wait a moment and reopen your library.',
                409,
            );
        }

        try {
            $existing = $user->bibleStudies()->where('request_hash', $requestHash)->first();

            if ($existing instanceof BibleStudy) {
                return ['study' => $existing, 'reused' => true];
            }

            $reservation = $this->usage->reserve($user, 'bible_study', $requestHash);
            try {
                $generated = $this->generator->generate($scripture, $depth);
            } catch (\Throwable $exception) {
                $this->usage->release($reservation);
                throw $exception;
            }
            $content = $generated['content'];

            $study = $user->bibleStudies()->create([
                'title' => $content['title'],
                'scripture_key' => $studyKey,
                'scripture_reference' => $scripture['reference'],
                'translation' => $scripture['translation'],
                'scripture_text' => $scripture['text'],
                'depth' => $depth,
                'content' => $content,
                'ai_provider' => $generated['provider'],
                'ai_model' => $generated['model'],
                'ai_response_id' => $generated['response_id'],
                'prompt_version' => $generated['prompt_version'],
                'request_hash' => $requestHash,
                'generated_at' => now(),
            ]);
            $this->usage->complete($reservation, (array) ($generated['usage'] ?? []));

            return ['study' => $study, 'reused' => false];
        } finally {
            $lock->release();
        }
    }

    /** @param array<string, mixed> $reference */
    private function readerPassage(array $reference): array
    {
        $passage = $this->reader->passage(
            (string) $reference['translation'],
            (string) $reference['book'],
            (int) $reference['chapter'],
            isset($reference['verse_start']) ? (int) $reference['verse_start'] : null,
            isset($reference['verse_end']) ? (int) $reference['verse_end'] : null,
        );

        return [
            'reference' => $passage['book']['name'].' '.$passage['chapter'].':'.$passage['verse_start'].'-'.$passage['verse_end'],
            'translation' => $passage['translation']['abbreviation'],
            'text' => collect($passage['verses'])->map(fn (array $verse): string => $verse['number'].' '.$verse['text'])->implode("\n"),
        ];
    }

    /** @param array<string, mixed> $reference */
    private function readerKey(array $reference): string
    {
        return implode(':', [
            'bible',
            $reference['translation'],
            $reference['book'],
            $reference['chapter'],
            $reference['verse_start'] ?? 'all',
            $reference['verse_end'] ?? 'all',
        ]);
    }
}
