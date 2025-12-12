<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
