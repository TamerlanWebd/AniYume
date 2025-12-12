<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'anime_id',
        'episode_number',
        'title',
        'player_url',
        'external_id',
        'external_source',
        'aired_at',
        'duration',
        'thumbnail_url',
    ];

    protected $casts = [
        'aired_at' => 'datetime',
    ];

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
