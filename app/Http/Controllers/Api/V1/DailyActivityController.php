<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DailyActivitySummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyActivityController extends Controller
{
    public function __invoke(Request $request, DailyActivitySummary $summary): JsonResponse
    {
        return response()->json(['data' => $summary->forUser($request->user())]);
    }
}
