<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'rules' => $this->rules,
            'min_players' => $this->min_players,
            'max_players' => $this->max_players,
            'icon' => $this->icon,
            'cover_gradient' => $this->cover_gradient,
            'is_active' => $this->is_active,
            'points_per_win' => $this->points_per_win,
            'points_per_draw' => $this->points_per_draw,
            'active_rooms_count' => $this->whenCounted('activeRooms'),
        ];
    }
}
