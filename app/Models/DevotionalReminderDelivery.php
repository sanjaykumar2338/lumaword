<?php

namespace App\Models;

use Database\Factories\DevotionalReminderDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevotionalReminderDelivery extends Model
{
    /** @use HasFactory<DevotionalReminderDeliveryFactory> */
    use HasFactory;

    protected $fillable = ['reminder_date', 'sent_at'];

    protected function casts(): array
    {
        return [
            'reminder_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
