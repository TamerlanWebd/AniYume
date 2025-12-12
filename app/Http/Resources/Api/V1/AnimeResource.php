<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'poster_url' => $this->poster_url,
            'rating' => $this->rating,
            'year' => $this->year,
            'status' => $this->status,
            'type' => $this->type,
            'number_of_episodes' => $this->number_of_episodes,
            'aired_from' => $this->aired_from?->format('Y-m-d'),
            'aired_to' => $this->aired_to?->format('Y-m-d'),
            'nsfw_flag' => $this->nsfw_flag,
            'popularity' => $this->popularity,
            'favorites' => $this->favorites,
            'external_id' => $this->external_id,
            'external_source' => $this->external_source,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
