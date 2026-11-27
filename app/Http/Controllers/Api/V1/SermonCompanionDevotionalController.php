<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SermonCompanionDevotionalDayResource;
use App\Http\Resources\V1\SermonCompanionDevotionalPlanResource;
use App\Jobs\GenerateSermonCompanionDevotional;
use App\Models\SermonCompanion;
use App\Models\SermonCompanionDevotionalDay;
use App\Models\SermonCompanionDevotionalPlan;
use App\Services\AiUsageService;
use App\Services\SermonCompanionScriptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SermonCompanionDevotionalController extends Controller
{
    public function store(Request $request, SermonCompanion $sermonCompanion, SermonCompanionScriptureService $scriptures, AiUsageService $usage): JsonResponse
    {
        abort_unless($sermonCompanion->user_id === $request->user()->id, 403);
        $days = $request->validate(['days_count' => ['required', 'in:5,7']])['days_count'];
        $plan = $sermonCompanion->devotionalPlans()->firstOrCreate(['days_count' => $days], ['user_id' => $request->user()->id, 'status' => 'queued']);
        if ($plan->status === 'completed') {
            return (new SermonCompanionDevotionalPlanResource($plan->load(['days', 'progress'])))->response();
        }
        $scriptures->forCompanion($sermonCompanion);
        $reservation = $usage->reserve($request->user(), 'sermon_companion', $request->header('Idempotency-Key') ?: 'companion:'.$sermonCompanion->id.':devotional:'.$days.':'.config('sermon_companion.ai.prompt_version'));
        $plan->update(['status' => 'queued', 'processing_error' => null]);
        GenerateSermonCompanionDevotional::dispatch($plan->id, $reservation->id);

        return (new SermonCompanionDevotionalPlanResource($plan->refresh()->load(['days', 'progress'])))->additional(['message' => "{$days}-day devotional generation queued."])->response()->setStatusCode(202);
    }

    public function days(Request $request, SermonCompanionDevotionalPlan $plan): SermonCompanionDevotionalDayResource|AnonymousResourceCollection
    {
        abort_unless($plan->user_id === $request->user()->id, 403);

        return SermonCompanionDevotionalDayResource::collection($plan->days()->orderBy('day_number')->get());
    }

    public function complete(Request $request, SermonCompanionDevotionalDay $day): SermonCompanionDevotionalDayResource
    {
        $plan = $day->plan;
        abort_unless($plan->user_id === $request->user()->id, 403);
        $day->update(['completed_at' => now()]);
        $completed = $plan->days()->whereNotNull('completed_at')->pluck('day_number')->sort()->values()->all();
        $progress = $plan->progress()->firstOrCreate(['user_id' => $request->user()->id], ['completed_days' => []]);
        $progress->update(['completed_days' => $completed, 'current_day' => min($plan->days_count, count($completed) + 1), 'completion_percentage' => (int) round(count($completed) / $plan->days_count * 100), 'last_activity_at' => now(), 'completed_at' => count($completed) === $plan->days_count ? now() : null]);

        return new SermonCompanionDevotionalDayResource($day->refresh());
    }
}
