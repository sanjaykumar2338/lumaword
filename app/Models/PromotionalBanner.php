<?php

namespace App\Models;

use Database\Factories\PromotionalBannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'image_url', 'image_alt', 'destination_url', 'placement', 'is_enabled', 'starts_at', 'ends_at'])]
class PromotionalBanner extends Model
{
    /** @use HasFactory<PromotionalBannerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
