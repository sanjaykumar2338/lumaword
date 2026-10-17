<?php

namespace App\Models;

use Database\Factories\YoutubeConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeConnection extends Model
{
    /** @use HasFactory<YoutubeConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'channel_title',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'scopes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
