<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSermonCompanionRequest;
use App\Http\Requests\Api\V1\UpdateSermonCompanionRequest;
use App\Http\Resources\V1\SermonCompanionResource;
use App\Models\SermonCompanion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SermonCompanionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SermonCompanionResource::collection($request->user()->sermonCompanions()->with('attachments')->latest()->paginate(12));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSermonCompanionRequest $request): JsonResponse
    {
        $companion = $request->user()->sermonCompanions()->create($request->validated() + ['status' => 'awaiting_review']);

        return (new SermonCompanionResource($companion))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SermonCompanion $sermonCompanion): SermonCompanionResource
    {
        abort_unless($sermonCompanion->user_id === request()->user()->id, 403);

        return new SermonCompanionResource($sermonCompanion->load(['attachments', 'devotionalPlans.progress']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSermonCompanionRequest $request, SermonCompanion $sermonCompanion): SermonCompanionResource
    {
        $sermonCompanion->update($request->validated() + (filled($request->validated('confirmed_notes')) ? ['reviewed_at' => now(), 'status' => 'ready'] : []));

        return new SermonCompanionResource($sermonCompanion->refresh()->load('attachments'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SermonCompanion $sermonCompanion): JsonResponse
    {
        abort_unless($sermonCompanion->user_id === request()->user()->id, 403);
        $sermonCompanion->delete();

        return response()->json(status: 204);
    }
}
