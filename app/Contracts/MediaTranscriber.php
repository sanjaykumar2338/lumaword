<?php

namespace App\Contracts;

use App\Models\Sermon;

interface MediaTranscriber
{
    /**
     * @return array{text: string, language: ?string, duration_seconds: ?int}
     */
    public function transcribe(Sermon $sermon): array;
}
