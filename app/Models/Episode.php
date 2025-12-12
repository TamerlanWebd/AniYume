<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 *     schema="Episode",
 *     type="object",
 *     title="Episode",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="episode_number", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Asteroid Blues"),
 *     @OA\Property(property="player_link", type="string", example="//kodik.info/serial/12345/abcdef/720p"),
 *     @OA\Property(property="translation_name", type="string", example="AniLibria"),
 *     @OA\Property(property="translation_type", type="string", example="voice"),
 *     @OA\Property(property="quality", type="string", example="720p"),
 *     @OA\Property(property="source", type="string", example="kodik")
 * )
 */

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
