<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SermonResource;
use App\Models\Sermon;
use App\Services\SermonProcessingDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SermonProcessingController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Sermon $sermon, SermonProcessingDispatcher $dispatcher): SermonResource
    {
        Gate::authorize('update', $sermon);
        $dispatched = $dispatcher->dispatch($sermon, $request->user());

        return (new SermonResource($sermon->refresh()->load('notes')))
            ->additional(['message' => $dispatched ? 'Sermon queued for processing.' : 'Sermon processing is already active.']);
    }
}
