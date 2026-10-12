<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSermonTranscriptRequest;
use App\Http\Resources\V1\SermonResource;
use App\Models\Sermon;
use App\SermonProcessingStatus;
use App\Services\SermonProcessingDispatcher;
use App\TranscriptSource;

class SermonTranscriptController extends Controller
{
    public function __invoke(
        StoreSermonTranscriptRequest $request,
        Sermon $sermon,
        SermonProcessingDispatcher $dispatcher,
    ): SermonResource {
        $sermon->notes()->delete();
        $sermon->update([
            'transcript' => $request->validated('transcript'),
            'transcript_source' => TranscriptSource::Pasted,
            'transcript_language' => $request->validated('transcript_language'),
            'processing_status' => SermonProcessingStatus::TranscriptReady,
            'processing_error' => null,
            'processed_at' => null,
        ]);

        if ($request->boolean('process', true)) {
            $dispatcher->dispatch($sermon, $request->user());
            $sermon->refresh();
        }

        return (new SermonResource($sermon->load('notes')))
            ->additional(['message' => $request->boolean('process', true)
                ? 'Transcript saved and queued for processing.'
                : 'Transcript saved.']);
    }
}
