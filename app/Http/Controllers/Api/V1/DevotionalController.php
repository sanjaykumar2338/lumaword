<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\DevotionalGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDevotionalRequest;
use App\Http\Resources\V1\DevotionalResource;
use App\Models\Devotional;
use App\Models\Sermon;
use App\SermonProcessingStatus;
use App\Services\AiUsageService;
use App\Services\BibleReaderService;
use App\Services\DailyDevotional;
use App\Services\ScriptureCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DevotionalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Devotional::class);

        return DevotionalResource::collection(
            $request->user()->devotionals()
                ->orderByDesc('generated_at')
                ->orderByDesc('id')
                ->paginate(12),
        );
    }

    public function today(DailyDevotional $daily): JsonResponse
    {
        return response()->json(['data' => $daily->forDate(today())]);
    }

    public function store(
        StoreDevotionalRequest $request,
        ScriptureCatalog $scriptures,
        DevotionalGenerator $generator,
        AiUsageService $usage,
    ): JsonResponse {
        Gate::authorize('create', Devotional::class);
        $scriptureKey = $request->validated('scripture_key');
        $scripture = $scriptures->find($scriptureKey);
        $sermonId = $request->validated('sermon_id');
        $sermon = $sermonId === null
            ? null
            : $request->user()->sermons()->findOrFail($sermonId);
        $idempotencyKey = $request->header('Idempotency-Key') ?: (string) Str::uuid();
        $reservation = $usage->reserve($request->user(), 'devotional', $idempotencyKey);

        try {
            $generated = $generator->generate($scripture, $sermon);
        } catch (Throwable $exception) {
            $usage->release($reservation);
            throw $exception;
        }

        $devotional = $request->user()->devotionals()->create([
            'sermon_id' => $sermon?->id,
            'source_type' => $sermon === null ? 'passage' : 'sermon',
            'title' => $generated['title'],
            'scripture_key' => $scriptureKey,
            'scripture_reference' => $scripture['reference'],
            'translation' => $scripture['translation'],
            'scripture_text' => $scripture['text'],
            'reflection' => $generated['reflection'],
            'application' => $generated['application'],
            'prayer' => $generated['prayer'],
            'ai_provider' => $generated['provider'],
            'ai_model' => $generated['model'],
            'prompt_version' => $generated['prompt_version'],
            'generated_at' => now(),
        ]);
        $usage->complete($reservation, (array) ($generated['usage'] ?? []));

        return (new DevotionalResource($devotional))->response()->setStatusCode(201);
    }

    public function show(Devotional $devotional): DevotionalResource
    {
        Gate::authorize('view', $devotional);

        return new DevotionalResource($devotional);
    }

    public function fromSermon(
        Request $request,
        Sermon $sermon,
        DevotionalGenerator $generator,
        BibleReaderService $reader,
        AiUsageService $usage,
    ): JsonResponse {
        Gate::authorize('view', $sermon);
        $sermon->loadMissing('notes');

        if ($sermon->processing_status !== SermonProcessingStatus::Completed || blank($sermon->transcript) || ! $sermon->notes) {
            throw ValidationException::withMessages([
                'sermon' => 'A completed sermon with a stored transcript and Scripture references is required.',
            ]);
        }

        $existing = $request->user()->devotionals()->where('sermon_id', $sermon->id)->latest('id')->first();
        if ($existing instanceof Devotional) {
            return (new DevotionalResource($existing))->additional(['meta' => ['reused' => true]])->response();
        }

        $scripture = $this->scriptureFromSermon($sermon, $reader);
        $lock = Cache::lock('sermon-devotional:'.$sermon->id, (int) config('devotionals.lock_seconds', 150));

        if (! $lock->get()) {
            throw ValidationException::withMessages(['sermon' => 'This sermon devotional is already being generated. Please wait a moment.']);
        }

        try {
            $existing = $request->user()->devotionals()->where('sermon_id', $sermon->id)->latest('id')->first();
            if ($existing instanceof Devotional) {
                return (new DevotionalResource($existing))->additional(['meta' => ['reused' => true]])->response();
            }

            $reservation = $usage->reserve($request->user(), 'devotional', 'sermon:'.$sermon->id.':'.config('devotionals.prompt_version'));
            try {
                $generated = $generator->generate($scripture, $sermon);
            } catch (Throwable $exception) {
                $usage->release($reservation);
                throw $exception;
            }

            $devotional = $request->user()->devotionals()->create([
                'sermon_id' => $sermon->id,
                'source_type' => 'sermon',
                'title' => $generated['title'],
                'scripture_key' => $scripture['key'],
                'scripture_reference' => $scripture['reference'],
                'translation' => $scripture['translation'],
                'scripture_text' => $scripture['text'],
                'reflection' => $generated['reflection'],
                'application' => $generated['application'],
                'prayer' => $generated['prayer'],
                'ai_provider' => $generated['provider'],
                'ai_model' => $generated['model'],
                'prompt_version' => $generated['prompt_version'],
                'generated_at' => now(),
            ]);
            $usage->complete($reservation, (array) ($generated['usage'] ?? []));

            return (new DevotionalResource($devotional))
                ->additional(['meta' => ['reused' => false]])
                ->response()
                ->setStatusCode(201);
        } finally {
            $lock->release();
        }
    }

    /** @return array{key: string, reference: string, translation: string, text: string} */
    private function scriptureFromSermon(Sermon $sermon, BibleReaderService $reader): array
    {
        foreach ((array) ($sermon->notes->scripture_references ?? []) as $reference) {
            if (! is_string($reference)) {
                continue;
            }

            $parsed = $reader->parseReference($reference);
            if ($parsed === null) {
                continue;
            }

            try {
                $book = $reader->resolveBookId((string) config('bible.default_translation'), $parsed['book']);
                $passage = $reader->passage(
                    (string) config('bible.default_translation'),
                    $book,
                    $parsed['chapter'],
                    $parsed['verse_start'],
                    $parsed['verse_end'],
                );

                return [
                    'key' => 'bible:'.$passage['translation']['abbreviation'].':'.$book.':'.$parsed['chapter'].':'.$parsed['verse_start'].':'.$parsed['verse_end'],
                    'reference' => $passage['book']['name'].' '.$passage['chapter'].':'.$passage['verse_start'].'-'.$passage['verse_end'],
                    'translation' => $passage['translation']['abbreviation'],
                    'text' => collect($passage['verses'])->map(fn (array $verse): string => $verse['number'].' '.$verse['text'])->implode("\n"),
                ];
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        throw ValidationException::withMessages(['sermon' => 'The completed sermon has no usable Scripture reference for a devotional.']);
    }
}
