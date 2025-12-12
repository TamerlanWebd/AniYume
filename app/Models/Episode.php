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
        'season_number',
        'title',
        'player_url',
        'player_iframe',
        'external_id',
        'external_source',
        'external_episode_id',
        'aired_at',
        'release_date',
        'duration',
        'thumbnail_url',
        'poster_url',
        'translator',
        'translation_type',
        'quality',
        'source',
        'priority',
    ];
    

    protected $casts = [
        'aired_at' => 'datetime',
        'release_date' => 'date',
    ];

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
