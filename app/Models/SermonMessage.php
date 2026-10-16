<?php

namespace App\Models;

use Database\Factories\SermonMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SermonMessage extends Model
{
    /** @use HasFactory<SermonMessageFactory> */
    use HasFactory;

    public const string ROLE_ASSISTANT = 'assistant';

    public const string ROLE_USER = 'user';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_FAILED = 'failed';

    public const string STATUS_PENDING = 'pending';

    protected $fillable = [
        'reply_to_message_id', 'role', 'content', 'sources', 'status', 'client_request_id',
        'ai_provider', 'ai_model', 'ai_response_id', 'usage',
    ];

    protected function casts(): array
    {
        return ['sources' => 'array', 'usage' => 'array'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SermonConversation::class, 'conversation_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function reply(): HasOne
    {
        return $this->hasOne(self::class, 'reply_to_message_id');
    }
}
