<?php

namespace App\Models;

use Database\Factories\SermonNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonNote extends Model
{
    /** @use HasFactory<SermonNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'summary', 'main_idea', 'outline', 'key_points', 'scripture_references',
        'themes', 'applications', 'discussion_questions', 'ai_provider', 'ai_model',
        'ai_response_id', 'prompt_version', 'usage',
    ];

    protected function casts(): array
    {
        return [
            'outline' => 'array',
            'key_points' => 'array',
            'scripture_references' => 'array',
            'themes' => 'array',
            'applications' => 'array',
            'discussion_questions' => 'array',
            'usage' => 'array',
        ];
    }

    public function sermon(): BelongsTo
    {
        return $this->belongsTo(Sermon::class);
    }
}
