<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AiUsageService;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function __invoke(Request $request, EntitlementService $entitlements, AiUsageService $usage): JsonResponse
    {
        return response()->json(['data' => [
            'membership' => $entitlements->subscription($request->user()),
            'usage' => $usage->summary($request->user()),
        ]]);
    }
}
