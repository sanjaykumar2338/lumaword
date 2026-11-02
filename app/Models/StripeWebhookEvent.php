<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['stripe_event_id', 'type', 'subject_id', 'stripe_created_at', 'status', 'processed_at'])]
class StripeWebhookEvent extends Model
{
    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
