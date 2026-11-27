<?php

namespace App\Models;

use Database\Factories\SermonCompanionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SermonCompanion extends Model
{
    /** @use HasFactory<SermonCompanionFactory> */
    use HasFactory;

    protected $fillable = ['title', 'speaker', 'church_name', 'sermon_date', 'youtube_url', 'personal_notes', 'scripture_references', 'key_takeaways', 'personal_reflections', 'confirmed_notes', 'recap', 'status', 'processing_error', 'reviewed_at', 'completed_at'];

    protected function casts(): array
    {
        return ['sermon_date' => 'date', 'scripture_references' => 'array', 'key_takeaways' => 'array', 'recap' => 'array', 'reviewed_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SermonCompanionAttachment::class);
    }

    public function devotionalPlans(): HasMany
    {
        return $this->hasMany(SermonCompanionDevotionalPlan::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(SermonCompanionJournalEntry::class);
    }

    public function memoryCards(): HasMany
    {
        return $this->hasMany(SermonCompanionMemoryCard::class);
    }
}
