<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminWebController extends Controller
{
    public function index(Request $request): View
    {
        $metrics = [
            'total_users' => User::count(),
            'online_users' => User::where('is_online', true)->count(),
            'suspended_users' => User::where('is_suspended', true)->count(),
            'total_games' => Game::count(),
            'active_games' => Game::where('is_active', true)->count(),
            'active_rooms' => GameRoom::whereIn('status', ['waiting', 'in_progress'])->count(),
            'total_sessions' => GameSession::count(),
            'completed_sessions' => GameSession::where('status', 'completed')->count(),
            'open_reports' => Report::where('status', 'open')->count(),
        ];

        $users = User::with('globalStatistic')->latest()->limit(25)->get();
        $games = Game::withCount(['rooms', 'activeRooms', 'sessions'])->get();
        $activeRooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(15)
            ->get();

        $recentSessions = GameSession::with(['game', 'room', 'winner'])
            ->latest()
            ->limit(15)
            ->get();

        $achievements = Achievement::with('game')->orderBy('points_reward')->get();
        $reports = Report::with(['reporter', 'reportedUser', 'gameRoom'])->latest()->limit(15)->get();

        return view('admin.dashboard', compact(
            'metrics',
            'users',
            'games',
            'activeRooms',
            'recentSessions',
            'achievements',
            'reports'
        ));
    }

    public function toggleSuspension(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['admin' => 'You cannot suspend your own administrator account.']);
        }

        $user->update([
            'is_suspended' => !$user->is_suspended,
            'is_online' => $user->is_suspended ? $user->is_online : false,
        ]);

        $statusText = $user->is_suspended ? 'suspended' : 'reactivated';

        return back()->with('success', "Player account @{$user->username} has been {$statusText}.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:admin,player'],
        ]);

        if ($user->id === $request->user()->id && $request->input('role') !== 'admin') {
            return back()->withErrors(['admin' => 'You cannot demote your own administrator account.']);
        }

        $user->update(['role' => $request->input('role')]);

        return back()->with('success', "Updated role for @{$user->username} to {$user->role}.");
    }

    public function toggleGame(Game $game): RedirectResponse
    {
        $game->update(['is_active' => !$game->is_active]);

        $state = $game->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Game \"{$game->name}\" has been {$state}.");
    }

    public function updateGamePoints(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'points_per_win' => ['required', 'integer', 'min:10', 'max:1000'],
            'points_per_draw' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $game->update($validated);

        return back()->with('success', "Updated scoring configuration for {$game->name}.");
    }

    public function closeRoom(GameRoom $room): RedirectResponse
    {
        $room->update([
            'status' => 'cancelled',
            'ended_at' => now(),
        ]);

        $room->sessions()->where('status', 'in_progress')->update([
            'status' => 'abandoned',
            'result_reason' => 'Closed by Administrator',
            'ended_at' => now(),
        ]);

        return back()->with('success', "Room #{$room->room_code} has been closed.");
    }

    public function storeAchievement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'badge_color' => ['nullable', 'string', 'max:30'],
            'points_reward' => ['required', 'integer', 'min:10', 'max:5000'],
            'criteria_type' => ['required', 'string', 'in:games_played,games_won,total_points,win_streak,friends_count,game_specific_wins'],
            'criteria_value' => ['required', 'integer', 'min:1'],
            'game_id' => ['nullable', 'exists:games,id'],
        ]);

        Achievement::create([
            ...$validated,
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
            'icon' => $validated['icon'] ?? 'trophy',
            'badge_color' => $validated['badge_color'] ?? 'purple',
        ]);

        return back()->with('success', "Achievement \"{$validated['name']}\" created!");
    }

    public function resolveReport(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? 'Reviewed by admin.',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', "Report #{$report->id} marked as {$validated['status']}.");
    }

    public function storeReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reported_user_id' => ['nullable', 'exists:users,id'],
            'game_room_id' => ['nullable', 'exists:game_rooms,id'],
            'reason' => ['required', 'string', 'max:100'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $validated['reported_user_id'] ?? null,
            'game_room_id' => $validated['game_room_id'] ?? null,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => 'open',
        ]);

        return back()->with('success', 'Your report has been submitted to platform moderators.');
    }
}
