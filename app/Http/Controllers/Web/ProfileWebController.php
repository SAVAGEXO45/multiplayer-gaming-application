<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Achievement;
use App\Models\GameSession;
use App\Models\PlayerStatistic;
use App\Models\User;
use App\Services\FriendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileWebController extends Controller
{
    public function __construct(
        private readonly FriendService $friendService
    ) {}

    public function show(Request $request, ?string $username = null): View
    {
        $profileUser = $username
            ? User::where('username', strtolower($username))->firstOrFail()
            : $request->user();

        $globalStat = PlayerStatistic::firstOrCreate(
            ['user_id' => $profileUser->id, 'game_id' => null],
            [
                'games_played' => 0,
                'games_won' => 0,
                'games_lost' => 0,
                'games_drawn' => 0,
                'win_percentage' => 0,
                'total_points' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
            ]
        );

        $gameStats = PlayerStatistic::with('game')
            ->where('user_id', $profileUser->id)
            ->whereNotNull('game_id')
            ->get();

        $allAchievements = Achievement::with('game')->orderBy('points_reward')->get();
        $unlockedMap = $profileUser->playerAchievements()
            ->pluck('unlocked_at', 'achievement_id')
            ->all();

        $userRoomIds = $profileUser->roomMemberships()->pluck('game_room_id');
        $recentMatches = GameSession::with(['game', 'room.roomPlayers.user', 'winner'])
            ->whereIn('game_room_id', $userRoomIds)
            ->where('status', 'completed')
            ->latest('ended_at')
            ->limit(10)
            ->get();

        $isOwnProfile = $request->user() && $request->user()->id === $profileUser->id;
        $isFriend = $request->user() && !$isOwnProfile
            ? $this->friendService->areFriends($request->user()->id, $profileUser->id)
            : false;

        return view('profile.show', compact(
            'profileUser',
            'globalStat',
            'gameStats',
            'allAchievements',
            'unlockedMap',
            'recentMatches',
            'isOwnProfile',
            'isFriend'
        ));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'username', 'bio']);
        $data['username'] = strtolower($data['username']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return redirect()->route('profile.show', $user->username)
            ->with('success', 'Your gaming profile has been updated!');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
