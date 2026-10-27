<?php

namespace App\Contracts;

use App\BibleStudyDepth;

interface BibleStudyGenerator
{
    /**
     * @param  array{reference: string, translation: string, text: string}  $scripture
     * @return array{content: array<string, mixed>, provider: string, model: string, prompt_version: string, response_id: ?string}
     */
    public function generate(array $scripture, BibleStudyDepth $depth): array;
}
