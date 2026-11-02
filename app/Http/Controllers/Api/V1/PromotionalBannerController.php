<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePromotionalBannerRequest;
use App\Http\Requests\Api\V1\UpdatePromotionalBannerRequest;
use App\Models\PromotionalBanner;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionalBannerController extends Controller
{
    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);

        return response()->json(['data' => PromotionalBanner::query()->latest('id')->get()]);
    }

    public function index(Request $request, EntitlementService $entitlements): JsonResponse
    {
        if ($entitlements->isPremium($request->user())) {
            return response()->json(['data' => []]);
        }

        $placement = $request->string('placement', 'dashboard')->toString();
        $banners = PromotionalBanner::query()->where('is_enabled', true)->where('placement', $placement)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->oldest('id')->get();

        return response()->json(['data' => $banners]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromotionalBannerRequest $request): JsonResponse
    {
        return response()->json(['data' => PromotionalBanner::create($request->validated())], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, PromotionalBanner $promotionalBanner): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);

        return response()->json(['data' => $promotionalBanner]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromotionalBannerRequest $request, PromotionalBanner $promotionalBanner): JsonResponse
    {
        $promotionalBanner->update($request->validated());

        return response()->json(['data' => $promotionalBanner->refresh()]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PromotionalBanner $promotionalBanner): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);
        $promotionalBanner->delete();

        return response()->json(status: 204);
    }
}
