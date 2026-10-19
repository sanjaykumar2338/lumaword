<?php

namespace App\Models;

use App\SermonProcessingStatus;
use App\SermonSourceType;
use App\TranscriptSource;
use Database\Factories\SermonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sermon extends Model
{
    /** @use HasFactory<SermonFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'source_type', 'source_url', 'source_provider', 'provider_video_id',
        'provider_caption_id', 'provider_retrieved_at', 'processing_status',
        'processing_error', 'transcript', 'transcript_source', 'transcript_language',
        'duration_seconds', 'media_path', 'media_original_name', 'media_mime',
        'processing_started_at', 'processing_attempts', 'processed_at',
    ];

    protected $hidden = ['media_path'];

    protected function casts(): array
    {
        return [
            'source_type' => SermonSourceType::class,
            'processing_status' => SermonProcessingStatus::class,
            'transcript_source' => TranscriptSource::class,
            'duration_seconds' => 'integer',
            'processing_attempts' => 'integer',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
            'provider_retrieved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notes(): HasOne
    {
        return $this->hasOne(SermonNote::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(SermonConversation::class);
    }

    public function devotionals(): HasMany
    {
        return $this->hasMany(Devotional::class);
    }
}
