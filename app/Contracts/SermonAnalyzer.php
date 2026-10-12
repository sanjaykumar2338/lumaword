<?php

namespace App\Contracts;

use App\Models\Sermon;

interface SermonAnalyzer
{
    /**
     * @return array{notes: array<string, mixed>, response_id: ?string, usage: array<string, mixed>}
     */
    public function analyze(Sermon $sermon): array;
}
