<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSermonMediaRequest;
use App\Http\Resources\V1\SermonResource;
use App\Models\Sermon;
use App\SermonProcessingStatus;
use App\Services\SermonProcessingDispatcher;
use App\TranscriptSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SermonMediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        StoreSermonMediaRequest $request,
        Sermon $sermon,
        SermonProcessingDispatcher $dispatcher,
    ): SermonResource {
        if ($sermon->processing_status->isActive()) {
            throw ValidationException::withMessages([
                'media' => 'Wait for the current sermon processing to finish before uploading another file.',
            ]);
        }

        $media = $request->file('media');
        $newPath = $media->store("sermons/{$request->user()->id}/media", 'local');
        $oldPath = $sermon->media_path;

        try {
            DB::transaction(function () use ($sermon, $newPath, $media, $dispatcher, $request): void {
                $sermon->notes()->delete();
                $sermon->conversations()->delete();
                $sermon->update([
                    'transcript' => null,
                    'transcript_source' => TranscriptSource::None,
                    'transcript_language' => null,
                    'provider_caption_id' => null,
                    'provider_retrieved_at' => null,
                    'media_path' => $newPath,
                    'media_original_name' => $media->getClientOriginalName(),
                    'media_mime' => $media->getMimeType(),
                    'processing_status' => SermonProcessingStatus::Submitted,
                    'processing_error' => null,
                    'processed_at' => null,
                ]);

                $dispatcher->dispatch($sermon, $request->user());
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);

            throw $exception;
        }

        if (is_string($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        return (new SermonResource($sermon->refresh()->load('notes')))
            ->additional(['message' => 'Authorized media uploaded and queued for private transcription.']);
    }
}
