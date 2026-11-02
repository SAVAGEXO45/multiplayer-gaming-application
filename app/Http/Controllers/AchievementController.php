<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    public function index(Request $request, AchievementService $achievements): Response
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

        return Inertia::render('achievements/Index', [
            'achievements' => $items,
            'summary' => [
                'unlocked' => $items->where('unlocked', true)->count(),
                'total' => $items->count(),
                'points_earned' => $items->where('unlocked', true)->sum('points_reward'),
            ],
        ]);
    }
}
