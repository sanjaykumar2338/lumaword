<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSermonCompanionMemoryPracticeRequest;
use App\Http\Resources\V1\SermonCompanionMemoryCardResource;
use App\Models\SermonCompanion;
use App\Models\SermonCompanionMemoryCard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SermonCompanionMemoryController extends Controller
{
    public function index(Request $request, SermonCompanion $sermonCompanion): AnonymousResourceCollection
    {
        abort_unless($sermonCompanion->user_id === $request->user()->id, 403);

        return SermonCompanionMemoryCardResource::collection($sermonCompanion->memoryCards()->with('practice')->get());
    }

    public function practice(StoreSermonCompanionMemoryPracticeRequest $request, SermonCompanionMemoryCard $memoryCard): SermonCompanionMemoryCardResource
    {
        $practice = $memoryCard->practice()->updateOrCreate(['user_id' => $request->user()->id], ['completed' => $request->boolean('completed'), 'attempts' => ((int) ($memoryCard->practice?->attempts ?? 0)) + 1, 'last_practiced_at' => now()]);

        return new SermonCompanionMemoryCardResource($memoryCard->refresh()->load('practice'));
    }
}
