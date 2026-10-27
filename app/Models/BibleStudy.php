<?php

namespace App\Models;

use App\BibleStudyDepth;
use Database\Factories\BibleStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleStudy extends Model
{
    /** @use HasFactory<BibleStudyFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'scripture_key',
        'scripture_reference',
        'translation',
        'scripture_text',
        'depth',
        'content',
        'personal_notes',
        'ai_provider',
        'ai_model',
        'ai_response_id',
        'prompt_version',
        'request_hash',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'depth' => BibleStudyDepth::class,
            'content' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
