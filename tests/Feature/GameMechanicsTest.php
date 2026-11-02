<?php

use App\Enums\RewardTransactionType;
use App\Models\Achievement;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\Reward;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\PlayerStatisticsService;
use App\Services\RewardService;
use App\Services\ScoringService;

it('awards points exactly once per reference', function () {
    $user = User::factory()->create();
    $scoring = app(ScoringService::class);

    $first = $scoring->award($user, 50, 'Test award', 'game_result', 123);
    $second = $scoring->award($user, 50, 'Test award', 'game_result', 123);

    expect($first)->not->toBeNull()
        ->and($first->id)->toBe($second->id)
        ->and($user->statistics->fresh()->points)->toBe(50)
        ->and(RewardTransaction::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('floors a deduction at zero and skips a zero-delta transaction', function () {
    $user = User::factory()->create();
    $scoring = app(ScoringService::class);

    $transaction = $scoring->award($user, -25, 'Loss penalty', 'game_result', 99);

    expect($transaction)->toBeNull()
        ->and($user->statistics->fresh()->points)->toBe(0)
        ->and(RewardTransaction::query()->count())->toBe(0);
});

it('unlocks an achievement once and awards its points once', function () {
    $user = User::factory()->create();
    $user->statistics->update(['wins' => 1]);

    $achievement = Achievement::factory()->create([
        'slug' => 'first-win',
        'name' => 'First Blood',
        'criteria_type' => 'wins',
        'criteria_value' => 1,
        'points_reward' => 25,
        'is_active' => true,
    ]);

    $service = app(AchievementService::class);

    $unlocked = $service->evaluate($user);

    expect($unlocked->pluck('id')->all())->toContain($achievement->id)
        ->and($user->achievements()->count())->toBe(1)
        ->and($user->statistics->fresh()->points)->toBe(25);

    $secondRun = $service->evaluate($user);

    expect($secondRun)->toBeEmpty()
        ->and($user->achievements()->count())->toBe(1)
        ->and($user->statistics->fresh()->points)->toBe(25)
        ->and(RewardTransaction::query()->where('reference_type', 'achievement')->count())->toBe(1);
});

it('does not unlock achievements below the criteria', function () {
    $user = User::factory()->create();
    $user->statistics->update(['wins' => 2]);

    Achievement::factory()->create([
        'criteria_type' => 'wins',
        'criteria_value' => 5,
        'is_active' => true,
    ]);

    $unlocked = app(AchievementService::class)->evaluate($user);

    expect($unlocked)->toBeEmpty()
        ->and($user->achievements()->count())->toBe(0);
});

it('ignores inactive achievements', function () {
    $user = User::factory()->create();
    $user->statistics->update(['wins' => 10]);

    Achievement::factory()->create([
        'criteria_type' => 'wins',
        'criteria_value' => 1,
        'is_active' => false,
    ]);

    expect(app(AchievementService::class)->evaluate($user))->toBeEmpty();
});

it('redeems a reward, deducts points and reduces stock', function () {
    $user = User::factory()->create();
    $user->statistics->update(['points' => 500]);

    $reward = Reward::factory()->create([
        'cost' => 200,
        'stock' => 3,
        'is_active' => true,
    ]);

    $transaction = app(RewardService::class)->redeem($user, $reward);

    expect($transaction->type)->toBe(RewardTransactionType::Spend)
        ->and($transaction->amount)->toBe(-200)
        ->and($transaction->balance_after)->toBe(300)
        ->and($user->statistics->fresh()->points)->toBe(300)
        ->and($reward->fresh()->stock)->toBe(2);
});

it('rejects duplicate reward purchases', function () {
    $user = User::factory()->create();
    $user->statistics->update(['points' => 500]);

    $reward = Reward::factory()->create(['cost' => 100, 'is_active' => true]);

    $service = app(RewardService::class);
    $service->redeem($user, $reward);

    expect(fn () => $service->redeem($user, $reward))
        ->toThrow(Illuminate\Validation\ValidationException::class);

    expect($user->statistics->fresh()->points)->toBe(400)
        ->and(RewardTransaction::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('rejects rewards the player cannot afford', function () {
    $user = User::factory()->create();
    $user->statistics->update(['points' => 50]);

    $reward = Reward::factory()->create(['cost' => 100, 'is_active' => true]);

    expect(fn () => app(RewardService::class)->redeem($user, $reward))
        ->toThrow(Illuminate\Validation\ValidationException::class);

    expect($user->statistics->fresh()->points)->toBe(50)
        ->and(RewardTransaction::query()->count())->toBe(0);
});

it('rejects inactive and out of stock rewards', function () {
    $user = User::factory()->create();
    $user->statistics->update(['points' => 500]);

    $inactive = Reward::factory()->create(['cost' => 100, 'is_active' => false]);
    $soldOut = Reward::factory()->create(['cost' => 100, 'is_active' => true, 'stock' => 0]);

    expect(fn () => app(RewardService::class)->redeem($user, $inactive))
        ->toThrow(Illuminate\Validation\ValidationException::class);

    expect(fn () => app(RewardService::class)->redeem($user, $soldOut))
        ->toThrow(Illuminate\Validation\ValidationException::class);

    expect($user->statistics->fresh()->points)->toBe(500);
});

it('recomputes player statistics from game results', function () {
    $game = createGame('tic-tac-toe');
    $user = User::factory()->create();

    $session = GameSession::factory()->create([
        'game_id' => $game->id,
        'created_by' => $user->id,
        'status' => 'finished',
        'state' => ['players' => [$user->id]],
    ]);

    GameResult::factory()->create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'game_id' => $game->id,
        'result' => 'win',
        'score' => 10,
        'points_awarded' => 100,
    ]);

    $second = GameSession::factory()->create([
        'game_id' => $game->id,
        'created_by' => $user->id,
        'status' => 'finished',
        'state' => ['players' => [$user->id]],
    ]);

    GameResult::factory()->create([
        'game_session_id' => $second->id,
        'user_id' => $user->id,
        'game_id' => $game->id,
        'result' => 'loss',
        'score' => 0,
        'points_awarded' => 5,
    ]);

    $statistics = app(PlayerStatisticsService::class)->recompute($user);

    expect($statistics->games_played)->toBe(2)
        ->and($statistics->wins)->toBe(1)
        ->and($statistics->losses)->toBe(1)
        ->and($statistics->total_score)->toBe(10);
});
