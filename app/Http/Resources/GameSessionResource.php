<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\Games\GameManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin \App\Models\GameSession */
class GameSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $engine = app(GameManager::class)->forSlug($this->game->slug);
        $state = $engine->publicState($this->resource, $viewer);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'difficulty' => $this->difficulty,
            'state' => $state,
            'current_turn_user_id' => $this->current_turn_user_id,
            'winner_id' => $this->winner_id,
            'is_draw' => $this->is_draw,
            'game' => new GameResource($this->whenLoaded('game')),
            'room_code' => $this->whenLoaded('room', fn () => $this->room?->code),
            'players' => UserResource::collection($this->resolvePlayers()),
            'results' => GameResultResource::collection($this->whenLoaded('results')),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve the players taking part in the session. Completed sessions expose
     * them through results, while active sessions only have their ids in state.
     *
     * @return Collection<int, User>
     */
    private function resolvePlayers(): Collection
    {
        if ($this->relationLoaded('results')) {
            $players = $this->results->pluck('user')->filter()->unique('id')->values();

            if ($players->isNotEmpty()) {
                return $players;
            }
        }

        $ids = $this->state['players'] ?? [];

        if (! is_array($ids) || $ids === []) {
            return collect();
        }

        $ordered = array_values(array_unique(array_map('intval', $ids)));
        $users = User::query()->whereIn('id', $ordered)->get()->keyBy('id');

        return collect($ordered)
            ->map(fn (int $id): ?User => $users->get($id))
            ->filter()
            ->values();
    }
}
