<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonCompanionDevotionalDay extends Model
{
    protected $fillable = ['day_number', 'title', 'theme', 'scripture_reference', 'translation', 'scripture_text', 'reflection', 'reflection_question', 'practical_application', 'prayer', 'completed_at'];

    protected function casts(): array
    {
        return ['day_number' => 'integer', 'completed_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SermonCompanionDevotionalPlan::class, 'sermon_companion_devotional_plan_id');
    }
}
