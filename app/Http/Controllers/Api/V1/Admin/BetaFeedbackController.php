<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BetaFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BetaFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $feedback = BetaFeedback::query()
            ->select(['id', 'user_id', 'category', 'message', 'created_at'])
            ->with('user:id,name,email')
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return response()->json([
            'data' => $feedback->items(),
            'links' => [
                'first' => $feedback->url(1),
                'last' => $feedback->url($feedback->lastPage()),
                'prev' => $feedback->previousPageUrl(),
                'next' => $feedback->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
            ],
        ]);
    }
}
