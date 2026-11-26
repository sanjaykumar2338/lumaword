<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = ['enabled', 'sermon_processing', 'devotional_reminders', 'subscription_updates', 'reading_plan_reminders', 'reminder_time', 'timezone'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sermon_processing' => 'boolean',
            'devotional_reminders' => 'boolean',
            'subscription_updates' => 'boolean',
            'reading_plan_reminders' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
