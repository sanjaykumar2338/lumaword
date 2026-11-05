<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(AdminDashboardService $dashboard): JsonResponse
    {
        return response()->json(['data' => $dashboard->summary()]);
    }
}
