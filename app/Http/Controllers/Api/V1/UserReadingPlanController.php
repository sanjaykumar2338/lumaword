<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserReadingPlanRequest;
use App\Http\Resources\V1\UserReadingPlanResource;
use App\Models\ReadingPlan;
use App\Models\UserReadingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserReadingPlanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', UserReadingPlan::class);

        return UserReadingPlanResource::collection(
            $request->user()->readingPlans()
                ->with(['readingPlan.days', 'progress'])
                ->withCount('progress')
                ->orderByRaw('completed_at IS NOT NULL')
                ->latest('started_at')
                ->get(),
        );
    }

    public function store(
        StoreUserReadingPlanRequest $request,
        ReadingPlan $readingPlan,
    ): JsonResponse {
        Gate::authorize('create', UserReadingPlan::class);
        abort_unless($readingPlan->is_published, 404);

        $enrollment = $request->user()->readingPlans()->firstOrCreate(
            ['reading_plan_id' => $readingPlan->id],
            ['started_at' => now()],
        );
        $enrollment->load(['readingPlan.days', 'progress'])->loadCount('progress');

        return (new UserReadingPlanResource($enrollment))
            ->additional(['message' => $enrollment->wasRecentlyCreated
                ? 'Reading plan started.'
                : 'You are already following this reading plan.'])
            ->response()
            ->setStatusCode($enrollment->wasRecentlyCreated ? 201 : 200);
    }

    public function show(UserReadingPlan $userReadingPlan): UserReadingPlanResource
    {
        Gate::authorize('view', $userReadingPlan);

        return new UserReadingPlanResource(
            $userReadingPlan->load(['readingPlan.days', 'progress'])->loadCount('progress'),
        );
    }
}
