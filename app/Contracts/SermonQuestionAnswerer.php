<?php

namespace App\Contracts;

use App\Models\Sermon;
use Illuminate\Support\Collection;

interface SermonQuestionAnswerer
{
    /**
     * @param  Collection<int, array{role: string, content: string}>  $recentMessages
     * @return array{content: string, sources: array<int, array{section: string, excerpt: string}>, response_id: ?string, usage: array<string, mixed>}
     */
    public function answer(Sermon $sermon, string $question, Collection $recentMessages, string $requestId): array;
}
