<?php

namespace App\Models;

use Database\Factories\DevotionalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devotional extends Model
{
    /** @use HasFactory<DevotionalFactory> */
    use HasFactory;

    protected $fillable = [
        'sermon_id',
        'source_type',
        'title',
        'scripture_key',
        'scripture_reference',
        'translation',
        'scripture_text',
        'reflection',
        'application',
        'prayer',
        'ai_provider',
        'ai_model',
        'prompt_version',
        'generated_at',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sermon(): BelongsTo
    {
        return $this->belongsTo(Sermon::class);
    }
}
