<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonCompanionJournalEntry extends Model
{
    protected $fillable = ['sermon_companion_id', 'user_id', 'devotional_day_id', 'entry_type', 'client_key', 'content'];

    public function companion(): BelongsTo
    {
        return $this->belongsTo(SermonCompanion::class, 'sermon_companion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function devotionalDay(): BelongsTo
    {
        return $this->belongsTo(SermonCompanionDevotionalDay::class, 'devotional_day_id');
    }
}
