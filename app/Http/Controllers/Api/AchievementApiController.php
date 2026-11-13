<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $achievements = Achievement::with('game:id,name,slug')->orderBy('points_reward')->get();
        $unlockedMap = [];

        if ($request->user()) {
            $unlockedMap = PlayerAchievement::where('user_id', $request->user()->id)
                ->pluck('unlocked_at', 'achievement_id')
                ->all();
        }

        $data = $achievements->map(fn (Achievement $a) => [
            'id' => $a->id,
            'name' => $a->name,
            'slug' => $a->slug,
            'description' => $a->description,
            'icon' => $a->icon,
            'badge_color' => $a->badge_color,
            'points_reward' => $a->points_reward,
            'criteria_type' => $a->criteria_type,
            'criteria_value' => $a->criteria_value,
            'game' => $a->game,
            'unlocked' => isset($unlockedMap[$a->id]),
            'unlocked_at' => isset($unlockedMap[$a->id]) ? (string) $unlockedMap[$a->id] : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
