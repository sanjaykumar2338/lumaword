<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SermonCompanionMemoryCard extends Model
{
    protected $fillable = ['reference', 'translation', 'verse_text', 'fill_in_blank', 'correct_answers'];

    protected function casts(): array
    {
        return ['fill_in_blank' => 'array', 'correct_answers' => 'array'];
    }

    public function companion(): BelongsTo
    {
        return $this->belongsTo(SermonCompanion::class, 'sermon_companion_id');
    }

    public function practice(): HasOne
    {
        return $this->hasOne(SermonCompanionMemoryPractice::class);
    }
}
