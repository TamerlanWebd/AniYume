<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'episode_number' => $this->episode_number,
            'title' => $this->title,
            'player_link' => $this->player_link,
            'screenshot_url' => $this->screenshot_url,
            'translation_name' => $this->translation_name,
            'translation_type' => $this->translation_type,
            'translation_id' => $this->translation_id,
            'quality' => $this->quality,
            'duration_minutes' => $this->duration_minutes,
            'source' => $this->source,
            'source_id' => $this->source_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
