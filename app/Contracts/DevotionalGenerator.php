<?php

namespace App\Contracts;

use App\Models\Sermon;

interface DevotionalGenerator
{
    /** @return array{title: string, reflection: string, application: string, prayer: string, provider: string, model: string, prompt_version: string} */
    public function generate(array $scripture, ?Sermon $sermon = null): array;
}
