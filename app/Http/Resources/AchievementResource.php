<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Achievement */
class AchievementResource extends JsonResource
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
            'description' => $this->description,
            'icon' => $this->icon,
            'tier' => $this->tier,
            'criteria_type' => $this->criteria_type,
            'criteria_value' => $this->criteria_value,
            'points_reward' => $this->points_reward,
            'unlocked' => (bool) ($this->unlocked ?? false),
            'unlocked_at' => $this->unlocked_at ?? null,
        ];
    }
}
