<?php

namespace App\Enums;

enum AchievementCriteria: string
{
    case Wins = 'wins';
    case GamesPlayed = 'games_played';
    case Streak = 'streak';
    case Points = 'points';
    case MultiplayerWins = 'multiplayer_wins';
    case TicTacToeWins = 'tictactoe_wins';
    case RpsWins = 'rps_wins';
    case NumberGuessWins = 'number_guess_wins';
}
