<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSermonCompanionJournalEntryRequest;
use App\Http\Resources\V1\SermonCompanionJournalEntryResource;
use App\Models\SermonCompanion;
use App\Models\SermonCompanionJournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SermonCompanionJournalController extends Controller
{
    public function index(Request $request, SermonCompanion $sermonCompanion): AnonymousResourceCollection
    {
        abort_unless($sermonCompanion->user_id === $request->user()->id, 403);

        return SermonCompanionJournalEntryResource::collection($sermonCompanion->journalEntries()->latest()->paginate(30));
    }

    public function store(StoreSermonCompanionJournalEntryRequest $request, SermonCompanion $sermonCompanion): SermonCompanionJournalEntryResource
    {
        $data = $request->validated();
        $entry = $sermonCompanion->journalEntries()->updateOrCreate(['user_id' => $request->user()->id, 'devotional_day_id' => $data['devotional_day_id'] ?? null, 'entry_type' => $data['entry_type'], 'client_key' => $data['client_key'] ?? null], ['content' => $data['content']]);

        return new SermonCompanionJournalEntryResource($entry);
    }

    public function destroy(Request $request, SermonCompanionJournalEntry $entry): JsonResponse
    {
        abort_unless($entry->user_id === $request->user()->id, 403);
        $entry->delete();

        return response()->json(status: 204);
    }
}
