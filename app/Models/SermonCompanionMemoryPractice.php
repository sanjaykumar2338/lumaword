<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonCompanionMemoryPractice extends Model
{
    protected $fillable = ['sermon_companion_memory_card_id', 'user_id', 'completed', 'attempts', 'last_practiced_at'];

    protected function casts(): array
    {
        return ['completed' => 'boolean', 'attempts' => 'integer', 'last_practiced_at' => 'datetime'];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(SermonCompanionMemoryCard::class, 'sermon_companion_memory_card_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
