<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSermonCompanionAttachmentRequest;
use App\Http\Resources\V1\SermonCompanionResource;
use App\Jobs\ProcessSermonCompanionAttachment;
use App\Models\SermonCompanion;

class SermonCompanionAttachmentController extends Controller
{
    public function __invoke(StoreSermonCompanionAttachmentRequest $request, SermonCompanion $sermonCompanion): SermonCompanionResource
    {
        $file = $request->file('file');
        $path = $file->store("sermon-companions/{$request->user()->id}/{$sermonCompanion->id}", 'local');
        $attachment = $sermonCompanion->attachments()->create(['kind' => $request->validated('kind'), 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'size_bytes' => $file->getSize(), 'status' => 'queued']);
        $sermonCompanion->update(['status' => 'extracting_notes']);
        ProcessSermonCompanionAttachment::dispatch($attachment->id);

        return new SermonCompanionResource($sermonCompanion->refresh()->load('attachments'));
    }
}
