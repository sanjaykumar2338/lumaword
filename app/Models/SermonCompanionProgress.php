<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonCompanionProgress extends Model
{
    protected $fillable = ['sermon_companion_devotional_plan_id', 'user_id', 'current_day', 'completed_days', 'completion_percentage', 'last_activity_at', 'completed_at', 'midweek_check_in'];

    protected function casts(): array
    {
        return ['current_day' => 'integer', 'completed_days' => 'array', 'completion_percentage' => 'integer', 'last_activity_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SermonCompanionDevotionalPlan::class, 'sermon_companion_devotional_plan_id');
    }
}
