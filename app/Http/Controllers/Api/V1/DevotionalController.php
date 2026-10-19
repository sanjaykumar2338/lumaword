<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\DevotionalGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDevotionalRequest;
use App\Http\Resources\V1\DevotionalResource;
use App\Models\Devotional;
use App\Services\DailyDevotional;
use App\Services\ScriptureCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DevotionalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Devotional::class);

        return DevotionalResource::collection(
            $request->user()->devotionals()
                ->orderByDesc('generated_at')
                ->orderByDesc('id')
                ->paginate(12),
        );
    }

    public function today(DailyDevotional $daily): JsonResponse
    {
        return response()->json(['data' => $daily->forDate(today())]);
    }

    public function store(
        StoreDevotionalRequest $request,
        ScriptureCatalog $scriptures,
        DevotionalGenerator $generator,
    ): JsonResponse {
        Gate::authorize('create', Devotional::class);
        $scriptureKey = $request->validated('scripture_key');
        $scripture = $scriptures->find($scriptureKey);
        $sermonId = $request->validated('sermon_id');
        $sermon = $sermonId === null
            ? null
            : $request->user()->sermons()->findOrFail($sermonId);
        $generated = $generator->generate($scripture, $sermon);

        $devotional = $request->user()->devotionals()->create([
            'sermon_id' => $sermon?->id,
            'source_type' => $sermon === null ? 'passage' : 'sermon',
            'title' => $generated['title'],
            'scripture_key' => $scriptureKey,
            'scripture_reference' => $scripture['reference'],
            'translation' => $scripture['translation'],
            'scripture_text' => $scripture['text'],
            'reflection' => $generated['reflection'],
            'application' => $generated['application'],
            'prayer' => $generated['prayer'],
            'ai_provider' => $generated['provider'],
            'ai_model' => $generated['model'],
            'prompt_version' => $generated['prompt_version'],
            'generated_at' => now(),
        ]);

        return (new DevotionalResource($devotional))->response()->setStatusCode(201);
    }

    public function show(Devotional $devotional): DevotionalResource
    {
        Gate::authorize('view', $devotional);

        return new DevotionalResource($devotional);
    }
}
