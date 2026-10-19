<?php

namespace App\Models;

use Database\Factories\ReadingPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPlan extends Model
{
    /** @use HasFactory<ReadingPlanFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'title', 'description', 'duration_days', 'is_published'];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function days(): HasMany
    {
        return $this->hasMany(ReadingPlanDay::class)->orderBy('day_number');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(UserReadingPlan::class);
    }
}
