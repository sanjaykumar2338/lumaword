<?php

namespace App\Http\Controllers\Api\V1;

use App\DailyActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AskSermonQuestionRequest;
use App\Http\Resources\V1\SermonConversationResource;
use App\Http\Resources\V1\SermonMessageResource;
use App\Models\Sermon;
use App\Models\SermonConversation;
use App\Services\AskSermonQuestion;
use App\Services\DailyActivityRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class SermonMessageController extends Controller
{
    public function index(Request $request, Sermon $sermon): AnonymousResourceCollection
    {
        Gate::authorize('view', $sermon);
        $perPage = (int) config('sermons.qa.messages_per_page');
        $conversation = SermonConversation::query()
            ->whereBelongsTo($request->user())
            ->whereBelongsTo($sermon)
            ->first();
        $messages = $conversation?->messages()
            ->latest('id')
            ->paginate($perPage);

        if ($messages === null) {
            $messages = new LengthAwarePaginator([], 0, $perPage, 1, ['path' => $request->url()]);
        } else {
            $messages->setCollection($messages->getCollection()->reverse()->values());
        }

        return SermonMessageResource::collection($messages);
    }

    public function store(
        AskSermonQuestionRequest $request,
        Sermon $sermon,
        AskSermonQuestion $questions,
        DailyActivityRecorder $activityRecorder,
    ): JsonResponse {
        $result = $questions->handle(
            $request->user(),
            $sermon,
            $request->validated('question'),
            $request->validated('client_request_id'),
        );
        $activityRecorder->record($request->user(), DailyActivityType::SermonStudy);

        return response()->json([
            'data' => [
                'conversation' => (new SermonConversationResource($result['conversation']))->resolve(),
                'question' => (new SermonMessageResource($result['question']))->resolve(),
                'answer' => (new SermonMessageResource($result['answer']))->resolve(),
            ],
        ], 201);
    }
}
