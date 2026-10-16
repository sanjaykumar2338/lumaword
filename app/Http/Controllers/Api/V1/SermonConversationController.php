<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SermonConversationResource;
use App\Models\Sermon;
use App\Models\SermonConversation;
use App\Services\AskSermonQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SermonConversationController extends Controller
{
    public function show(Sermon $sermon, AskSermonQuestion $questions): JsonResponse
    {
        Gate::authorize('view', $sermon);
        $conversation = SermonConversation::query()
            ->whereBelongsTo(request()->user())
            ->whereBelongsTo($sermon)
            ->withCount('messages')
            ->first();
        $reason = $questions->unavailableReason($sermon);

        return response()->json([
            'data' => $conversation === null ? null : (new SermonConversationResource($conversation))->resolve(),
            'availability' => ['available' => $reason === null, 'reason' => $reason],
        ]);
    }
}
