<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBetaFeedbackRequest;
use Illuminate\Http\JsonResponse;

class BetaFeedbackController extends Controller
{
    public function store(StoreBetaFeedbackRequest $request): JsonResponse
    {
        $feedback = $request->user()->betaFeedback()->create([
            'category' => $request->validated('category') ?? 'other',
            'message' => $request->validated('message'),
        ]);

        return response()->json([
            'data' => $feedback,
            'message' => 'Thank you. Your feedback has been shared with the LUMA team.',
        ], 201);
    }
}
