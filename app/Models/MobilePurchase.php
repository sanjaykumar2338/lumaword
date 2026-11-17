<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilePurchase extends Model
{
    protected $fillable = [
        'user_id', 'store', 'product_id', 'transaction_id', 'original_transaction_id', 'status',
        'purchased_at', 'expires_at', 'last_verified_at', 'verification_data', 'provider_response',
    ];

    protected $hidden = ['verification_data', 'provider_response'];

    protected function casts(): array
    {
        return [
            'verification_data' => 'encrypted',
            'provider_response' => 'array',
            'purchased_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
