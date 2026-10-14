<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexSermonRequest;
use App\Http\Requests\Api\V1\StoreSermonRequest;
use App\Http\Requests\Api\V1\UpdateSermonRequest;
use App\Http\Resources\V1\SermonResource;
use App\Models\Sermon;
use App\SermonProcessingStatus;
use App\SermonSourceType;
use App\Services\SermonProcessingDispatcher;
use App\Services\SermonProcessingMonitor;
use App\Services\SermonUrlService;
use App\TranscriptSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SermonController extends Controller
{
    public function index(IndexSermonRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Sermon::class);

        $filters = $request->validated();

        $query = $request->user()->sermons()
            ->select([
                'id', 'user_id', 'title', 'source_type', 'source_url', 'source_provider',
                'processing_status', 'processing_error', 'transcript_source', 'transcript_language',
                'duration_seconds', 'processing_started_at', 'processed_at', 'created_at', 'updated_at',
            ])
            ->selectRaw("CASE WHEN transcript IS NULL OR transcript = '' THEN 0 ELSE 1 END AS has_transcript")
            ->when(isset($filters['search']), function ($query) use ($filters): void {
                $search = addcslashes($filters['search'], '%_\\');
                $query->where('title', 'like', "%{$search}%");
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('processing_status', $filters['status']))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return SermonResource::collection($query->paginate($filters['per_page'] ?? 12)->withQueryString());
    }

    public function store(
        StoreSermonRequest $request,
        SermonUrlService $urlService,
        SermonProcessingDispatcher $dispatcher,
    ): JsonResponse {
        Gate::authorize('create', Sermon::class);
        $data = $request->validated();
        $sourceType = SermonSourceType::from($data['source_type']);
        $user = $request->user();
        $mediaPath = null;

        try {
            if ($sourceType === SermonSourceType::Upload) {
                $mediaPath = $request->file('media')->store("sermons/{$user->id}/media", 'local');
            }

            $sourceUrl = $sourceType === SermonSourceType::Url
                ? $urlService->normalize($data['source_url'])
                : null;

            $sermon = $user->sermons()->create([
                'title' => ($data['title'] ?? null) ?: $this->defaultTitle($sourceType, $request->file('media')?->getClientOriginalName()),
                'source_type' => $sourceType,
                'source_url' => $sourceUrl,
                'source_provider' => $sourceUrl ? $urlService->provider($sourceUrl) : null,
                'processing_status' => match ($sourceType) {
                    SermonSourceType::Url => SermonProcessingStatus::AwaitingTranscript,
                    SermonSourceType::Transcript => SermonProcessingStatus::TranscriptReady,
                    SermonSourceType::Upload => SermonProcessingStatus::Submitted,
                },
                'transcript' => $sourceType === SermonSourceType::Transcript ? $data['transcript'] : null,
                'transcript_source' => $sourceType === SermonSourceType::Transcript ? TranscriptSource::Pasted : TranscriptSource::None,
                'transcript_language' => $data['transcript_language'] ?? null,
                'media_path' => $mediaPath,
                'media_original_name' => $request->file('media')?->getClientOriginalName(),
                'media_mime' => $request->file('media')?->getMimeType(),
            ]);
        } catch (Throwable $exception) {
            if (is_string($mediaPath)) {
                Storage::disk('local')->delete($mediaPath);
            }

            throw $exception;
        }

        if ($sourceType !== SermonSourceType::Url && ($data['auto_process'] ?? true)) {
            $dispatcher->dispatch($sermon, $user);
            $sermon->refresh();
        }

        return (new SermonResource($sermon))
            ->additional(['message' => $sourceType === SermonSourceType::Url
                ? 'Sermon link saved. Add an authorized transcript to continue.'
                : 'Sermon submitted for background processing.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Sermon $sermon, SermonProcessingMonitor $monitor): SermonResource
    {
        Gate::authorize('view', $sermon);
        $monitor->failIfStale($sermon);

        return new SermonResource($sermon->load('notes'));
    }

    public function update(UpdateSermonRequest $request, Sermon $sermon): SermonResource
    {
        $sermon->update($request->validated());

        return new SermonResource($sermon->load('notes'));
    }

    public function destroy(Sermon $sermon): JsonResponse
    {
        Gate::authorize('delete', $sermon);

        if (is_string($sermon->media_path)) {
            Storage::disk('local')->delete($sermon->media_path);
        }

        $sermon->delete();

        return response()->json(status: 204);
    }

    private function defaultTitle(SermonSourceType $sourceType, ?string $originalName): string
    {
        if ($sourceType === SermonSourceType::Upload && is_string($originalName)) {
            return mb_substr(pathinfo($originalName, PATHINFO_FILENAME), 0, 180) ?: 'Untitled sermon';
        }

        return 'Untitled sermon';
    }
}
