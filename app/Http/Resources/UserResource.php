<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isSelf = $viewer?->id === $this->id;
        $isAdmin = (bool) $viewer?->isAdmin();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($isSelf || $isAdmin, $this->email),
            'avatar_url' => $this->avatarUrl(),
            'bio' => $this->bio,
            'role' => $this->role->value,
            'is_suspended' => $this->is_suspended,
            'points' => (int) ($this->statistics?->points ?? 0),
            'statistics' => new PlayerStatisticsResource($this->whenLoaded('statistics')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
