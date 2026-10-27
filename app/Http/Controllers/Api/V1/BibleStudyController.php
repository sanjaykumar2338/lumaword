<?php

namespace App\Http\Controllers\Api\V1;

use App\BibleStudyDepth;
use App\Exceptions\BibleStudyGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBibleStudyRequest;
use App\Http\Requests\Api\V1\UpdateBibleStudyRequest;
use App\Http\Resources\V1\BibleStudyResource;
use App\Models\BibleStudy;
use App\Services\BibleStudyCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BibleStudyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', BibleStudy::class);

        return BibleStudyResource::collection(
            $request->user()->bibleStudies()
                ->orderByDesc('generated_at')
                ->orderByDesc('id')
                ->paginate(12),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBibleStudyRequest $request, BibleStudyCreator $creator): JsonResponse
    {
        Gate::authorize('create', BibleStudy::class);

        try {
            $result = $creator->create(
                $request->user(),
                $request->validated('scripture_key'),
                BibleStudyDepth::from($request->validated('depth')),
            );
        } catch (BibleStudyGenerationException $exception) {
            if ($exception->httpStatus >= 500) {
                report($exception);
            }

            return response()->json(['message' => $exception->getMessage()], $exception->httpStatus);
        }

        return (new BibleStudyResource($result['study']))
            ->additional(['meta' => ['reused' => $result['reused']]])
            ->response()
            ->setStatusCode($result['reused'] ? 200 : 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BibleStudy $bibleStudy): BibleStudyResource
    {
        Gate::authorize('view', $bibleStudy);

        return new BibleStudyResource($bibleStudy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBibleStudyRequest $request, BibleStudy $bibleStudy): BibleStudyResource
    {
        Gate::authorize('update', $bibleStudy);

        $bibleStudy->update([
            'personal_notes' => $request->validated('personal_notes'),
        ]);

        return new BibleStudyResource($bibleStudy->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BibleStudy $bibleStudy): JsonResponse
    {
        Gate::authorize('delete', $bibleStudy);

        return response()->json(['message' => 'Bible studies cannot be deleted in this phase.'], 405);
    }
}
