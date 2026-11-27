<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonCompanionAttachment extends Model
{
    protected $fillable = ['kind', 'path', 'original_name', 'mime_type', 'size_bytes', 'status', 'extracted_text', 'is_unclear', 'processing_error', 'processed_at'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'is_unclear' => 'boolean', 'processed_at' => 'datetime'];
    }

    public function companion(): BelongsTo
    {
        return $this->belongsTo(SermonCompanion::class, 'sermon_companion_id');
    }
}
