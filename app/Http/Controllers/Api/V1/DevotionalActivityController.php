<?php

namespace App\Http\Controllers\Api\V1;

use App\DailyActivityType;
use App\Http\Controllers\Controller;
use App\Services\DailyActivityRecorder;
use App\Services\DailyActivitySummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevotionalActivityController extends Controller
{
    public function __invoke(
        Request $request,
        DailyActivityRecorder $recorder,
        DailyActivitySummary $summary,
    ): JsonResponse {
        $recorder->record($request->user(), DailyActivityType::DevotionalRead);

        return response()->json([
            'data' => $summary->forUser($request->user()),
            'message' => 'Today’s devotional activity has been recorded.',
        ]);
    }
}
