<?php

namespace App\Models;

use Database\Factories\DailyActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyActivity extends Model
{
    /** @use HasFactory<DailyActivityFactory> */
    use HasFactory;

    protected $fillable = ['activity_date', 'timezone', 'devotional_read', 'reading_plan_completed', 'sermon_study'];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'devotional_read' => 'boolean',
            'reading_plan_completed' => 'boolean',
            'sermon_study' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
