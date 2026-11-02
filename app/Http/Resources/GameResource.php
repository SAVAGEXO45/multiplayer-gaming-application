<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Game */
class GameResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'rules' => $this->rules,
            'icon' => $this->icon,
            'accent' => $this->accent,
            'min_players' => $this->min_players,
            'max_players' => $this->max_players,
            'supports_multiplayer' => $this->supports_multiplayer,
            'is_active' => $this->is_active,
            'points_win' => $this->points_win,
            'points_draw' => $this->points_draw,
            'points_loss' => $this->points_loss,
            'points_participation' => $this->points_participation,
            'difficulties' => $this->difficultyOptions(),
        ];
    }
}
