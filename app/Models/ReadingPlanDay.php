<?php

namespace App\Models;

use Database\Factories\ReadingPlanDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPlanDay extends Model
{
    /** @use HasFactory<ReadingPlanDayFactory> */
    use HasFactory;

    protected $fillable = ['day_number', 'title', 'scripture_key', 'scripture_reference', 'chapter_assignments'];

    protected function casts(): array
    {
        return ['day_number' => 'integer', 'chapter_assignments' => 'array'];
    }

    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReadingPlanProgress::class);
    }
}
