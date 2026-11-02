<?php

namespace App\Models;

use Database\Factories\AiUsageReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'feature', 'idempotency_key', 'status', 'period_start', 'period_end', 'input_tokens', 'output_tokens', 'cost_micros', 'completed_at', 'released_at'])]
class AiUsageReservation extends Model
{
    /** @use HasFactory<AiUsageReservationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['period_start' => 'datetime', 'period_end' => 'datetime', 'completed_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
