<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VerifyMobilePurchaseRequest;
use App\Services\AiUsageService;
use App\Services\EntitlementService;
use App\Services\MobilePurchaseVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePurchaseController extends Controller
{
    public function verify(
        VerifyMobilePurchaseRequest $request,
        MobilePurchaseVerificationService $verification,
        EntitlementService $entitlements,
        AiUsageService $usage,
    ): JsonResponse {
        $data = $request->validated();
        $verification->verify($request->user(), ...array_values($data));

        return response()->json(['data' => [
            'membership' => $entitlements->subscription($request->user()),
            'usage' => $usage->summary($request->user()),
        ]]);
    }

    public function status(Request $request, EntitlementService $entitlements, AiUsageService $usage): JsonResponse
    {
        return response()->json(['data' => [
            'membership' => $entitlements->subscription($request->user()),
            'usage' => $usage->summary($request->user()),
        ]]);
    }
}
