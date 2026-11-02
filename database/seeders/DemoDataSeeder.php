<?php

namespace Database\Seeders;

use App\Enums\GameResultType;
use App\Enums\GameSessionStatus;
use App\Enums\UserRole;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\PlayerStatistics;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@arcade.test'],
            [
                'name' => 'Arcade Admin',
                'username' => 'arcade_admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
                'bio' => 'Keeper of the arcade. Here to keep things fair.',
            ],
        );

        $player = User::query()->updateOrCreate(
            ['email' => 'player@arcade.test'],
            [
                'name' => 'Pixel Hero',
                'username' => 'pixel_hero',
                'password' => Hash::make('password'),
                'role' => UserRole::Player,
                'email_verified_at' => now(),
                'bio' => 'Chasing high scores one game at a time.',
            ],
        );

        $rivals = [
            ['name' => 'Nova Strike', 'username' => 'nova_strike', 'bio' => 'Fast fingers, faster reflexes.'],
            ['name' => 'Byte Bandit', 'username' => 'byte_bandit', 'bio' => 'Stealing wins since level one.'],
            ['name' => 'Luna Logic', 'username' => 'luna_logic', 'bio' => 'Every puzzle has a solution.'],
            ['name' => 'Ghost Protocol', 'username' => 'ghost_protocol', 'bio' => 'You never see me coming.'],
            ['name' => 'Turbo Tina', 'username' => 'turbo_tina', 'bio' => 'Full throttle, always.'],
            ['name' => 'Quiet Storm', 'username' => 'quiet_storm', 'bio' => 'Calm outside, chaos inside.'],
            ['name' => 'Ace Of Spades', 'username' => 'ace_of_spades', 'bio' => 'Luck is a skill.'],
            ['name' => 'Circuit Breaker', 'username' => 'circuit_breaker', 'bio' => 'Shorting the competition.'],
        ];

        /** @var Collection<int, User> $opponents */
        $opponents = collect($rivals)->map(fn (array $data): User => User::query()->updateOrCreate(
            ['email' => $data['username'].'@arcade.test'],
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make('password'),
                'role' => UserRole::Player,
                'email_verified_at' => now(),
                'bio' => $data['bio'],
            ],
        ));

        $games = Game::query()->get()->keyBy('slug');

        $this->seedStatistics($player, 18, 11, 4, 3, 2450, 6);
        $this->seedStatistics($admin, 4, 1, 2, 1, 320, 1);

        foreach ($opponents as $index => $opponent) {
            $wins = max(2, 20 - ($index * 2));
            $losses = 5 + $index;
            $draws = $index % 4;

            $this->seedStatistics(
                $opponent,
                $wins + $losses + $draws,
                $wins,
                $losses,
                $draws,
                max(150, 3800 - ($index * 420)),
                max(1, 8 - $index),
            );
        }

        $this->seedResults($player, $games, $opponents);
        $this->seedResults($admin, $games, $opponents->take(2)->values());

        foreach (User::query()->get() as $user) {
            PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);
        }
    }

    private function seedStatistics(
        User $user,
        int $played,
        int $wins,
        int $losses,
        int $draws,
        int $points,
        int $bestStreak,
    ): void {
        PlayerStatistics::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'games_played' => $played,
                'wins' => $wins,
                'losses' => $losses,
                'draws' => $draws,
                'points' => $points,
                'current_streak' => min($bestStreak, 3),
                'best_streak' => $bestStreak,
                'multiplayer_wins' => intdiv($wins, 3),
                'total_score' => $wins * 10,
                'last_game_at' => now()->subHours(random_int(1, 48)),
            ],
        );
    }

    /**
     * @param  Collection<string, Game>  $games
     * @param  Collection<int, User>  $opponents
     */
    private function seedResults(User $user, Collection $games, Collection $opponents): void
    {
        $types = [
            GameResultType::Win,
            GameResultType::Win,
            GameResultType::Loss,
            GameResultType::Win,
            GameResultType::Draw,
            GameResultType::Loss,
            GameResultType::Win,
        ];

        $gameList = $games->values();

        foreach ($types as $index => $type) {
            $game = $gameList->get($index % max(1, $gameList->count()));

            if (! $game instanceof Game) {
                continue;
            }

            $opponent = $opponents->values()->get($index % max(1, $opponents->count()));
            $createdAt = Carbon::now()->subDays(random_int(0, 20))->subMinutes(random_int(0, 1440));

            $session = GameSession::query()->create([
                'game_id' => $game->id,
                'created_by' => $user->id,
                'status' => GameSessionStatus::Finished,
                'difficulty' => 'medium',
                'state' => [
                    'players' => array_values(array_filter([$user->id, $opponent?->id])),
                    'board' => array_fill(0, 9, null),
                ],
                'current_turn_user_id' => null,
                'winner_id' => match ($type) {
                    GameResultType::Win => $user->id,
                    GameResultType::Loss => $opponent?->id,
                    default => null,
                },
                'is_draw' => $type === GameResultType::Draw,
                'started_at' => $createdAt,
                'finished_at' => $createdAt->copy()->addMinutes(4),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            GameResult::query()->create([
                'game_session_id' => $session->id,
                'user_id' => $user->id,
                'game_id' => $game->id,
                'result' => $type,
                'score' => $type === GameResultType::Win ? random_int(6, 10) : random_int(0, 4),
                'points_awarded' => $this->pointsFor($game, $type),
                'is_multiplayer' => $opponent !== null,
                'metadata' => ['difficulty' => 'medium'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($opponent !== null) {
                $opponentType = match ($type) {
                    GameResultType::Win => GameResultType::Loss,
                    GameResultType::Loss => GameResultType::Win,
                    default => GameResultType::Draw,
                };

                GameResult::query()->create([
                    'game_session_id' => $session->id,
                    'user_id' => $opponent->id,
                    'game_id' => $game->id,
                    'result' => $opponentType,
                    'score' => $opponentType === GameResultType::Win ? random_int(6, 10) : random_int(0, 4),
                    'points_awarded' => $this->pointsFor($game, $opponentType),
                    'is_multiplayer' => true,
                    'metadata' => ['difficulty' => 'medium'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    private function pointsFor(Game $game, GameResultType $type): int
    {
        return match ($type) {
            GameResultType::Win => $game->points_win,
            GameResultType::Draw => $game->points_draw,
            GameResultType::Loss => $game->points_loss,
        };
    }
}
