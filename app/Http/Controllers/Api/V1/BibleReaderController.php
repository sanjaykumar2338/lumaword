<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BibleReaderRequest;
use App\Services\BibleReaderService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class BibleReaderController extends Controller
{
    public function translations(BibleReaderService $reader): JsonResponse
    {
        return response()->json(['data' => $reader->translations()]);
    }

    public function books(BibleReaderRequest $request, BibleReaderService $reader): JsonResponse
    {
        return response()->json(['data' => $reader->books($this->translation($request))]);
    }

    public function chapters(BibleReaderRequest $request, BibleReaderService $reader, string $book): JsonResponse
    {
        return response()->json(['data' => $reader->chapters($this->translation($request), $book)]);
    }

    public function passage(BibleReaderRequest $request, BibleReaderService $reader, string $book, int $chapter): JsonResponse
    {
        try {
            return response()->json(['data' => $reader->passage(
                $this->translation($request),
                $book,
                $chapter,
                $request->validated('verse_start'),
                $request->validated('verse_end'),
            )]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function search(BibleReaderRequest $request, BibleReaderService $reader): JsonResponse
    {
        $query = trim((string) $request->validated('q'));
        if ($query === '') {
            return response()->json(['message' => 'Enter a word or Scripture reference to search.'], 422);
        }

        try {
            return response()->json([
                'data' => $reader->search($this->translation($request), $query, (int) ($request->validated('per_page') ?? 20)),
                'meta' => ['query' => $query],
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    private function translation(BibleReaderRequest $request): string
    {
        return (string) ($request->validated('translation') ?? config('bible.default_translation'));
    }
}
