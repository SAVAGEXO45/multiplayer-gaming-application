<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\PlayerStatisticsResource;
use App\Http\Resources\RewardResource;
use App\Http\Resources\RewardTransactionResource;
use App\Http\Resources\UserResource;
use App\Models\Achievement;
use App\Models\PlayerStatistics;
use App\Models\Reward;
use App\Models\RewardTransaction;
use App\Services\AchievementService;
use App\Services\LeaderboardService;
use App\Services\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    use ApiResponse;

    public function profile(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        $user = $request->user()->load('statistics');

        return $this->success([
            'user' => new UserResource($user),
            'rank' => $leaderboard->rankOf($user),
            'per_game' => $leaderboard->perGameStats($user),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $request->user()->id]);

        return $this->success(new PlayerStatisticsResource($statistics));
    }

    public function achievements(Request $request, AchievementService $achievements): JsonResponse
    {
        $user = $request->user();
        $unlocked = $user->achievements()->get()->keyBy('achievement_id');

        $items = Achievement::query()
            ->where('is_active', true)
            ->orderBy('criteria_type')
            ->orderBy('criteria_value')
            ->get()
            ->map(function (Achievement $achievement) use ($user, $unlocked, $achievements): array {
                $unlock = $unlocked->get($achievement->id);

                return [
                    'id' => $achievement->id,
                    'slug' => $achievement->slug,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'tier' => $achievement->tier,
                    'criteria_type' => $achievement->criteria_type,
                    'criteria_value' => $achievement->criteria_value,
                    'points_reward' => $achievement->points_reward,
                    'unlocked' => $unlock !== null,
                    'unlocked_at' => $unlock?->unlocked_at?->toIso8601String(),
                    'progress' => min($achievement->criteria_value, $achievements->progress($user, $achievement)),
                ];
            });

        return $this->success($items);
    }

    public function rewards(Request $request): JsonResponse
    {
        $user = $request->user();
        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);

        $ownedIds = RewardTransaction::query()
            ->where('user_id', $user->id)
            ->where('reference_type', 'reward_purchase')
            ->whereNotNull('reward_id')
            ->pluck('reward_id')
            ->all();

        $rewards = Reward::query()
            ->where('is_active', true)
            ->orderBy('cost')
            ->get()
            ->each(fn (Reward $reward) => $reward->setAttribute('owned', in_array($reward->id, $ownedIds, true)));

        return $this->success([
            'balance' => $statistics->points,
            'rewards' => RewardResource::collection($rewards),
            'transactions' => RewardTransactionResource::collection(
                RewardTransaction::query()
                    ->where('user_id', $user->id)
                    ->with('reward')
                    ->latest()
                    ->limit(25)
                    ->get()
            ),
        ]);
    }

    public function redeemReward(Request $request, Reward $reward, RewardService $rewards): JsonResponse
    {
        $transaction = $rewards->redeem($request->user(), $reward);

        return $this->success(
            new RewardTransactionResource($transaction->load('reward')),
            "Reward claimed: {$reward->name}.",
        );
    }
}
