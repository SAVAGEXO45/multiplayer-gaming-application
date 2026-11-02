<?php

namespace App\Services;

use App\Enums\RewardTransactionType;
use App\Models\PlayerStatistics;
use App\Models\RewardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    /**
     * Award (or deduct) points for a user inside a transaction-safe operation.
     * Returns null when the balance would not change (e.g. already at zero).
     */
    public function award(
        User $user,
        int $amount,
        string $reason,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $rewardId = null,
        array $metadata = [],
    ): ?RewardTransaction {
        return DB::transaction(function () use ($user, $amount, $reason, $referenceType, $referenceId, $rewardId, $metadata): ?RewardTransaction {
            if ($referenceType !== null && $referenceId !== null) {
                $existing = RewardTransaction::query()
                    ->where('user_id', $user->id)
                    ->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            /** @var PlayerStatistics $statistics */
            $statistics = PlayerStatistics::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = max(0, $statistics->points + $amount);
            $delta = $newBalance - $statistics->points;

            if ($delta === 0 && $amount !== 0) {
                return null;
            }

            $statistics->update(['points' => $newBalance]);

            return RewardTransaction::create([
                'user_id' => $user->id,
                'reward_id' => $rewardId,
                'type' => $amount >= 0 ? RewardTransactionType::Earn : RewardTransactionType::Spend,
                'amount' => $delta,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'metadata' => $metadata ?: null,
            ]);
        });
    }

    public function spend(User $user, int $cost, string $reason, ?int $rewardId = null): RewardTransaction
    {
        $transaction = $this->award($user, -$cost, $reason, 'reward_purchase', $rewardId, $rewardId);

        if ($transaction === null || $transaction->amount === 0) {
            throw new \RuntimeException('Insufficient points for this reward.');
        }

        return $transaction;
    }
}
