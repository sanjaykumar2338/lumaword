<?php

namespace App\Models;

use Database\Factories\UserReadingPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserReadingPlan extends Model
{
    /** @use HasFactory<UserReadingPlanFactory> */
    use HasFactory;

    protected $fillable = ['reading_plan_id', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
