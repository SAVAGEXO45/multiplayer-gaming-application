<?php

namespace App\Services;

use App\Enums\RewardTransactionType;
use App\Models\PlayerStatistics;
use App\Models\Reward;
use App\Models\RewardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardService
{
    public function __construct(private readonly ScoringService $scoring) {}

    public function redeem(User $user, Reward $reward): RewardTransaction
    {
        return DB::transaction(function () use ($user, $reward): RewardTransaction {
            /** @var Reward $locked */
            $locked = Reward::query()->lockForUpdate()->findOrFail($reward->id);

            if (! $locked->isAvailable()) {
                throw ValidationException::withMessages(['reward' => 'This reward is no longer available.']);
            }

            $alreadyOwned = RewardTransaction::query()
                ->where('user_id', $user->id)
                ->where('reward_id', $locked->id)
                ->where('reference_type', 'reward_purchase')
                ->exists();

            if ($alreadyOwned) {
                throw ValidationException::withMessages(['reward' => 'You already claimed this reward.']);
            }

            /** @var PlayerStatistics $statistics */
            $statistics = PlayerStatistics::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($statistics->points < $locked->cost) {
                throw ValidationException::withMessages(['reward' => 'You do not have enough points for this reward.']);
            }

            $transaction = $this->scoring->award(
                $user,
                -$locked->cost,
                "Redeemed reward: {$locked->name}",
                'reward_purchase',
                $locked->id,
                $locked->id,
                ['reward' => $locked->slug, 'type' => $locked->type],
            );

            if ($transaction === null || $transaction->amount === 0 || $transaction->type !== RewardTransactionType::Spend) {
                throw ValidationException::withMessages(['reward' => 'You do not have enough points for this reward.']);
            }

            if ($locked->stock !== null) {
                $locked->decrement('stock');
            }

            return $transaction;
        });
    }
}
