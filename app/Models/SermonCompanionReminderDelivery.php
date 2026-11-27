<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonCompanionReminderDelivery extends Model
{
    protected $fillable = ['sermon_companion_devotional_plan_id', 'user_id', 'kind', 'reminder_date', 'sent_at'];

    protected function casts(): array
    {
        return ['reminder_date' => 'date', 'sent_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SermonCompanionDevotionalPlan::class, 'sermon_companion_devotional_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
