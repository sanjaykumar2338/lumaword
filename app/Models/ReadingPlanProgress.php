<?php

namespace App\Models;

use Database\Factories\ReadingPlanProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlanProgress extends Model
{
    /** @use HasFactory<ReadingPlanProgressFactory> */
    use HasFactory;

    protected $fillable = ['reading_plan_day_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(UserReadingPlan::class, 'user_reading_plan_id');
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(ReadingPlanDay::class, 'reading_plan_day_id');
    }
}
