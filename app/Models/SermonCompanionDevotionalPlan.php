<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SermonCompanionDevotionalPlan extends Model
{
    protected $fillable = ['sermon_companion_id', 'user_id', 'days_count', 'status', 'processing_error', 'generated_at', 'completed_at'];

    protected function casts(): array
    {
        return ['days_count' => 'integer', 'generated_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function companion(): BelongsTo
    {
        return $this->belongsTo(SermonCompanion::class, 'sermon_companion_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(SermonCompanionDevotionalDay::class, 'sermon_companion_devotional_plan_id');
    }

    public function progress(): HasOne
    {
        return $this->hasOne(SermonCompanionProgress::class, 'sermon_companion_devotional_plan_id');
    }
}
