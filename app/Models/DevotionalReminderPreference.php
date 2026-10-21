<?php

namespace App\Models;

use Database\Factories\DevotionalReminderPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevotionalReminderPreference extends Model
{
    /** @use HasFactory<DevotionalReminderPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['enabled', 'reminder_time', 'timezone'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
