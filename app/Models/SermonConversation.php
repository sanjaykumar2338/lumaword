<?php

namespace App\Models;

use Database\Factories\SermonConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SermonConversation extends Model
{
    /** @use HasFactory<SermonConversationFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'sermon_id', 'title'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sermon(): BelongsTo
    {
        return $this->belongsTo(Sermon::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SermonMessage::class, 'conversation_id');
    }
}
