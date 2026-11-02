<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RewardTransaction */
class RewardTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'reason' => $this->reason,
            'reward' => new RewardResource($this->whenLoaded('reward')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
