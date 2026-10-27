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
        private readonly BibleStudyGenerator $generator,
    ) {}

    /** @return array{study: BibleStudy, reused: bool} */
    public function create(User $user, string $scriptureKey, BibleStudyDepth $depth): array
    {
        $requestHash = hash('sha256', implode('|', [
            $scriptureKey,
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

            $scripture = $this->scriptures->find($scriptureKey);
            $generated = $this->generator->generate($scripture, $depth);
            $content = $generated['content'];

            $study = $user->bibleStudies()->create([
                'title' => $content['title'],
                'scripture_key' => $scriptureKey,
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

            return ['study' => $study, 'reused' => false];
        } finally {
            $lock->release();
        }
    }
}
