<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameResult;
use App\Models\PlayerStatistics;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function global(int $limit = 50): array
    {
        $rows = PlayerStatistics::query()
            ->join('users', 'users.id', '=', 'player_statistics.user_id')
            ->where('users.is_suspended', false)
            ->select([
                'player_statistics.user_id',
                'player_statistics.points',
                'player_statistics.wins',
                'player_statistics.losses',
                'player_statistics.draws',
                'player_statistics.games_played',
                'player_statistics.best_streak',
                'users.username',
                'users.name',
                'users.avatar_path',
            ])
            ->orderByDesc('player_statistics.points')
            ->orderByDesc('player_statistics.wins')
            ->orderByDesc('player_statistics.games_played')
            ->orderBy('player_statistics.user_id')
            ->limit($limit)
            ->get();

        return $this->formatRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function period(string $period = 'weekly', ?Game $game = null, int $limit = 50): array
    {
        $query = GameResult::query()
            ->join('users', 'users.id', '=', 'game_results.user_id')
            ->where('users.is_suspended', false)
            ->when($game !== null, fn ($builder) => $builder->where('game_results.game_id', $game->id))
            ->when($period === 'weekly', fn ($builder) => $builder->where('game_results.created_at', '>=', now()->startOfWeek()))
            ->when($period === 'monthly', fn ($builder) => $builder->where('game_results.created_at', '>=', now()->startOfMonth()))
            ->selectRaw('game_results.user_id, users.username, users.name, users.avatar_path,
                COUNT(*) as games_played,
                SUM(CASE WHEN game_results.result = ? THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN game_results.result = ? THEN 1 ELSE 0 END) as losses,
                SUM(CASE WHEN game_results.result = ? THEN 1 ELSE 0 END) as draws,
                SUM(game_results.points_awarded) as points', ['win', 'loss', 'draw'])
            ->groupBy('game_results.user_id', 'users.username', 'users.name', 'users.avatar_path')
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->orderByDesc('games_played')
            ->limit($limit);

        return $this->formatRows($query->get());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forPeriod(string $period, ?Game $game = null, int $limit = 50): array
    {
        return $period === 'all'
            ? $this->global($limit)
            : $this->period($period, $game, $limit);
    }

    public function rankOf(User $user, string $period = 'all', ?Game $game = null): ?int
    {
        $entries = $this->forPeriod($period, $game, 5000);

        foreach ($entries as $entry) {
            if ((int) $entry['user_id'] === $user->id) {
                return (int) $entry['rank'];
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function perGameStats(User $user): array
    {
        return GameResult::query()
            ->join('games', 'games.id', '=', 'game_results.game_id')
            ->where('game_results.user_id', $user->id)
            ->selectRaw('games.slug, games.name, COUNT(*) as games_played,
                SUM(CASE WHEN game_results.result = ? THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN game_results.result = ? THEN 1 ELSE 0 END) as losses,
                SUM(CASE WHEN game_results.result = ? THEN 1 ELSE 0 END) as draws,
                SUM(game_results.points_awarded) as points', ['win', 'loss', 'draw'])
            ->groupBy('games.slug', 'games.name')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->slug => [
                    'slug' => $row->slug,
                    'name' => $row->name,
                    'games_played' => (int) $row->games_played,
                    'wins' => (int) $row->wins,
                    'losses' => (int) $row->losses,
                    'draws' => (int) $row->draws,
                    'points' => (int) $row->points,
                    'win_rate' => $row->games_played > 0
                        ? round(((int) $row->wins / (int) $row->games_played) * 100, 1)
                        : 0.0,
                ],
            ])
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function formatRows(Collection $rows): array
    {
        return $rows->values()->map(function ($row, int $index): array {
            $games = (int) $row->games_played;
            $wins = (int) $row->wins;

            return [
                'rank' => $index + 1,
                'user_id' => (int) $row->user_id,
                'username' => $row->username,
                'name' => $row->name,
                'avatar_url' => $row->avatar_path
                    ? asset('storage/'.$row->avatar_path)
                    : 'https://api.dicebear.com/9.x/bottts-neutral/svg?seed='.urlencode($row->username ?? $row->name),
                'points' => (int) $row->points,
                'wins' => $wins,
                'losses' => (int) $row->losses,
                'draws' => (int) $row->draws,
                'games_played' => $games,
                'best_streak' => (int) ($row->best_streak ?? 0),
                'win_rate' => $games > 0 ? round(($wins / $games) * 100, 1) : 0.0,
            ];
        })->all();
    }
}
