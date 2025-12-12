<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 *     schema="Anime",
 *     type="object",
 *     title="Anime",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Cowboy Bebop"),
 *     @OA\Property(property="title_english", type="string", example="Cowboy Bebop"),
 *     @OA\Property(property="title_japanese", type="string", example="カウボーイビバップ"),
 *     @OA\Property(property="slug", type="string", example="cowboy-bebop"),
 *     @OA\Property(property="type", type="string", example="TV"),
 *     @OA\Property(property="status", type="string", example="completed"),
 *     @OA\Property(property="episodes_count", type="integer", example=26),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="poster_url", type="string"),
 *     @OA\Property(property="rating", type="number", format="float", example=8.75),
 *     @OA\Property(property="popularity", type="integer", example=95000),
 *     @OA\Property(property="aired_from", type="string", format="date", example="1998-04-03"),
 *     @OA\Property(property="aired_to", type="string", format="date", example="1999-04-24")
 * )
 */

class Anime extends Model
{
    use HasFactory;

    protected $table = 'anime';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'poster_url',
        'rating',
        'year',
        'status',
        'type',
        'number_of_episodes',
        'external_id',
        'external_source',
        'aired_from',
        'aired_to',
        'nsfw_flag',
        'popularity',
        'favorites',
        'score_count',
    ];

    protected $casts = [
        'aired_from' => 'date',
        'aired_to' => 'date',
        'nsfw_flag' => 'boolean',
        'rating' => 'decimal:1',
    ];

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'anime_tag');
    }
}
