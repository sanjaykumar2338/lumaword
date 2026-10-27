<?php

namespace App;

enum BibleStudyDepth: string
{
    case QuickRead = 'quick_read';
    case GoDeeper = 'go_deeper';
    case StudyInDepth = 'study_in_depth';

    public function label(): string
    {
        return match ($this) {
            self::QuickRead => 'Quick Read',
            self::GoDeeper => 'Go Deeper',
            self::StudyInDepth => 'Study in Depth',
        };
    }

    public function guidance(): string
    {
        return match ($this) {
            self::QuickRead => 'Be concise and accessible. Prioritize a plain-language overview, two or three themes, and a few focused questions.',
            self::GoDeeper => 'Give expanded explanation, key themes, historical context, literary observations, and carefully explained cross-references.',
            self::StudyInDepth => 'Give detailed interpretation, historical and cultural background, literary structure, original-language observations, scholarly uncertainty, and explained cross-references.',
        };
    }
}
