<?php

namespace App\Http\Controllers;

use App\Models\PlayerStatistics;
use App\Models\Reward;
use App\Models\RewardTransaction;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RewardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);

        $ownedIds = RewardTransaction::query()
            ->where('user_id', $user->id)
            ->where('reference_type', 'reward_purchase')
            ->whereNotNull('reward_id')
            ->pluck('reward_id')
            ->all();

        return Inertia::render('rewards/Index', [
            'balance' => $statistics->points,
            'rewards' => Reward::query()
                ->where('is_active', true)
                ->orderBy('cost')
                ->get()
                ->map(fn (Reward $reward): array => [
                    'id' => $reward->id,
                    'slug' => $reward->slug,
                    'name' => $reward->name,
                    'description' => $reward->description,
                    'type' => $reward->type,
                    'icon' => $reward->icon,
                    'cost' => $reward->cost,
                    'stock' => $reward->stock,
                    'owned' => in_array($reward->id, $ownedIds, true),
                    'available' => $reward->isAvailable(),
                ]),
            'transactions' => RewardTransaction::query()
                ->where('user_id', $user->id)
                ->with('reward')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function redeem(Request $request, Reward $reward, RewardService $rewards): RedirectResponse
    {
        $rewards->redeem($request->user(), $reward);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Reward claimed: {$reward->name}."]);

        return back();
    }
}
