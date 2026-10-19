<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ReadingPlanResource;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReadingPlanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = ReadingPlan::query()
            ->where('is_published', true)
            ->with([
                'days',
                'enrollments' => fn ($query) => $query
                    ->whereBelongsTo($request->user())
                    ->with('progress'),
            ])
            ->orderBy('duration_days')
            ->get();

        return ReadingPlanResource::collection($plans);
    }

    public function show(Request $request, ReadingPlan $readingPlan): ReadingPlanResource
    {
        abort_unless($readingPlan->is_published, 404);

        return new ReadingPlanResource($readingPlan->load([
            'days',
            'enrollments' => fn ($query) => $query
                ->whereBelongsTo($request->user())
                ->with('progress'),
        ]));
    }
}
