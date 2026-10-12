<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SermonNoteResource;
use App\Models\Sermon;
use Illuminate\Support\Facades\Gate;

class SermonNotesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Sermon $sermon): SermonNoteResource
    {
        Gate::authorize('view', $sermon);

        abort_if($sermon->notes === null, 404, 'Sermon notes are not available yet.');

        return new SermonNoteResource($sermon->notes);
    }
}
