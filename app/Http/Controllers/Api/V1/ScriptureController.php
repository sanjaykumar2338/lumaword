<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ScriptureCatalog;
use Illuminate\Http\JsonResponse;

class ScriptureController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ScriptureCatalog $scriptures): JsonResponse
    {
        return response()->json([
            'data' => $scriptures->options()->values(),
            'source' => $scriptures->source(),
        ]);
    }
}
