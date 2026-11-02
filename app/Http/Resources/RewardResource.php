<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Reward */
class RewardResource extends JsonResource
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
            'type' => $this->type,
            'icon' => $this->icon,
            'cost' => $this->cost,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'available' => $this->isAvailable(),
            'owned' => (bool) ($this->owned ?? false),
        ];
    }
}
