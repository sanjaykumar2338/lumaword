<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sermon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SermonStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Sermon $sermon): JsonResponse
    {
        Gate::authorize('view', $sermon);

        return response()->json(['data' => [
            'id' => $sermon->id,
            'processing_status' => $sermon->processing_status->value,
            'processing_error' => $sermon->processing_error,
            'processed_at' => $sermon->processed_at?->toISOString(),
        ]]);
    }
}
