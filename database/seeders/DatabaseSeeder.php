<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\FriendRequest;
use App\Models\Friendship;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\PlayerStatistic;
use App\Models\Report;
use App\Models\User;
use App\Models\UserGameActivity;
use App\Services\RoomService;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with 10 playable Action Games.
     */
    public function run(): void
    {
        // Remove any legacy board games if present
        Game::whereIn('slug', [
            'tic-tac-toe',
            'rock-paper-scissors',
            'connect-four',
            'chess',
        ])->delete();

        $gamesCatalog = [
            [
                'slug' => 'cyber-strike-fps',
                'name' => 'Cyber Strike: Tactical Ops',
                'category' => 'Shooting',
                'description' => 'First-person tactical crosshair shooter. Lock onto armored cyber-mercenaries, sniper drones, and heavy mechs across Neo-Tokyo sectors using assault rifles, plasma shotguns, and railguns.',
                'instructions' => "1. Aim with your Mouse or Touch Crosshair and Left-Click / Tap FIRE to shoot.\n2. Press R to reload your magazine and keys 1, 2, 3 to switch between Assault Rifle, Plasma Shotgun, and Rail Sniper.\n3. Land headshots for 2.5x critical damage and clear each wave before your tactical shield depletes!",
                'rules' => "• Eliminate all hostile units in each wave to advance to higher-threat sectors.\n• Headshots grant bonus combo multipliers and rapid shield regeneration.\n• Every 3rd wave deploys an Armored Cyber-Boss with multi-phase missiles.",
                'controls_summary' => 'Mouse / Touch Aim • Left Click: Shoot • R: Reload • 1/2/3: Switch Weapons • P: Pause',
                'min_players' => 2,
                'max_players' => 4,
                'icon' => 'crosshair',
                'cover_gradient' => 'from-orange-600 via-red-600 to-slate-900',
                'thumbnail' => '/images/ref/shooter.jpg',
                'is_active' => true,
                'is_featured' => true,
                'is_trending' => true,
                'is_new_release' => false,
                'rating' => 4.9,
                'plays_count' => 14820,
                'points_per_win' => 200,
                'points_per_draw' => 60,
            ],
            [
                'slug' => 'nitro-apex-gt',
                'name' => 'Nitro Apex GT: Street Horizon',
                'category' => 'Car Racing',
                'description' => 'High-octane highway supercar racing. Weave through midnight neon traffic at 320 KM/H, draft behind rival racers, trigger twin-turbo Nitro boosts, and beat the checkpoint clock.',
                'instructions' => "1. Use A/D or Left/Right Arrow keys to steer across the 4-lane highway.\n2. Hold W / Up Arrow to accelerate, S / Down Arrow to brake, and SPACE / SHIFT to ignite Nitro Boost.\n3. Near-miss traffic cars at high speed to refill your Nitro tank and multiply your score!",
                'rules' => "• Reach each sector checkpoint before the countdown hits zero.\n• Colliding with traffic reduces speed and hull integrity.\n• Slipstream near-misses award +150 Style Points and +20% Nitro charge.",
                'controls_summary' => 'WASD / Arrows: Steer & Throttle • SPACE / SHIFT: Nitro Boost • P: Pause',
                'min_players' => 2,
                'max_players' => 4,
                'icon' => 'car',
                'cover_gradient' => 'from-blue-600 via-indigo-600 to-purple-900',
                'thumbnail' => '/images/ref/racing.jpg',
                'is_active' => true,
                'is_featured' => true,
                'is_trending' => true,
                'is_new_release' => false,
                'rating' => 4.9,
                'plays_count' => 12940,
                'points_per_win' => 180,
                'points_per_draw' => 50,
            ],
            [
                'slug' => 'shadow-dojo-brawl',
                'name' => 'Shadow Dojo: Iron Fist',
                'category' => 'Fighting',
                'description' => '2D martial arts tournament fighter. Master precision combos, aerial kicks, blocks, kunai plasma blasts, and Dragon Rage finishers against escalating AI grandmasters.',
                'instructions' => "1. Move with A/D or Left/Right Arrows; Jump with W / Up Arrow; Hold S / Down Arrow to Guard.\n2. Press J for Quick Strike, K for Heavy Roundhouse Kick, and L for Chi Fireball.\n3. Fill your Rage Bar to 100% and press SPACE to unleash the unstoppable Dragon Shadow Ultimate!",
                'rules' => "• Best-of-3 tournament rounds per opponent; depleting the rival's health bar wins the round.\n• Blocking reduces incoming strike damage by 80%.\n• Defeating each Grandmaster unlocks a faster, more aggressive martial arts challenger.",
                'controls_summary' => 'A/D: Move • W: Jump • S: Block • J: Strike • K: Heavy Kick • L: Fireball • SPACE: Ultimate',
                'min_players' => 2,
                'max_players' => 2,
                'icon' => 'swords',
                'cover_gradient' => 'from-red-600 via-rose-700 to-zinc-950',
                'thumbnail' => '/images/ref/hero-warrior-hd.jpg',
                'is_active' => true,
                'is_featured' => true,
                'is_trending' => true,
                'is_new_release' => true,
                'rating' => 4.8,
                'plays_count' => 11350,
                'points_per_win' => 220,
                'points_per_draw' => 65,
            ],
            [
                'slug' => 'emberfall-survivors',
                'name' => 'Emberfall: Blade & Ruin',
                'category' => 'Adventure',
                'description' => 'Action RPG survival adventure. Explore cursed ruins, slash through goblin raiders and obsidian golems, collect XP crystals, open golden treasure chests, and evolve arcane weapons.',
                'instructions' => "1. Move in 8 directions using WASD or Arrow keys.\n2. Press SPACE or Left-Click to perform a 360° Whirlwind Blade Slash; press SHIFT to Dash-Dodge with invincibility frames.\n3. Collect cyan XP Gems and Golden Chests to level up your blade damage, orbital fireballs, and chain lightning!",
                'rules' => "• Survive waves of dungeon monsters and defeat the Abyssal Warden boss.\n• Every level-up automatically upgrades your weapon arsenal and restores 25% HP.\n• Dash-dodging through attacks grants temporary speed and crit boosts.",
                'controls_summary' => 'WASD / Arrows: Move • Click / SPACE: Sword Slash • SHIFT: Dash Dodge • P: Pause',
                'min_players' => 2,
                'max_players' => 4,
                'icon' => 'shield',
                'cover_gradient' => 'from-amber-500 via-orange-600 to-stone-900',
                'thumbnail' => '/images/ref/sandbox.jpg',
                'is_active' => true,
                'is_featured' => true,
                'is_trending' => false,
                'is_new_release' => true,
                'rating' => 4.8,
                'plays_count' => 9870,
                'points_per_win' => 190,
                'points_per_draw' => 55,
            ],
            [
                'slug' => 'deadzone-outbreak',
                'name' => 'Deadzone: Outbreak 2099',
                'category' => 'Zombie Survival',
                'description' => '360-degree twin-stick zombie horde survival. Sweep your tactical flashlight through pitch-black quarantine zones, mow down infected sprinters and toxic boomers, and detonate frag grenades.',
                'instructions' => "1. Move with WASD / Arrow keys and aim your tactical flashlight + laser sight with the Mouse.\n2. Hold Left-Click to fire your automatic carbine; press G or Right-Click to throw a high-explosive Frag Grenade.\n3. Scavenge dropped Ammo Crates, Medkits, and Overcharge Turrets to survive the Blood Moon waves!",
                'rules' => "• Survive escalating zombie outbreak waves without letting the infected breach your armor.\n• Green Acid Boomers explode on death—keep your distance!\n• Supply drops spawn every 45 seconds with heavy ammo and grenades.",
                'controls_summary' => 'WASD: Move • Mouse: Aim Flashlight & Shoot • G / Space: Frag Grenade • R: Reload',
                'min_players' => 2,
                'max_players' => 4,
                'icon' => 'skull',
                'cover_gradient' => 'from-emerald-600 via-teal-800 to-slate-950',
                'thumbnail' => '/images/games/hero-arena.jpg',
                'is_active' => true,
                'is_featured' => false,
                'is_trending' => true,
                'is_new_release' => false,
                'rating' => 4.9,
                'plays_count' => 13410,
                'points_per_win' => 210,
                'points_per_draw' => 60,
            ],
            [
                'slug' => 'street-hoops-pro',
                'name' => 'Street Hoops 3x3: Pro Court',
                'category' => 'Sports',
                'description' => 'Fast-paced physics street basketball showdown. Dribble past AI defenders, time your release meter for splash 3-pointers, ignite Fire Ball streaks, and throw down thunderous alley-oop dunks.',
                'instructions' => "1. Move your player across the court with A/D or Left/Right Arrows.\n2. Aim your shot arc with Mouse or W/S keys, then hold & release SPACE / Left-Click in the Green Perfect Zone to shoot!\n3. Press SHIFT / K near the paint to leap for an unblockable Turbo Slam Dunk!",
                'rules' => "• Score as many points as possible before the 60-second quarter clock expires—beating the target score advances to the next Pro Court.\n• 3 consecutive swishes ignite ON FIRE mode (2x points & flaming basketball).\n• Shots beyond the arc count for 3 points (6 points while On Fire).",
                'controls_summary' => 'A/D: Court Move • Hold & Release SPACE / Click: Shoot • SHIFT: Slam Dunk',
                'min_players' => 2,
                'max_players' => 2,
                'icon' => 'flame',
                'cover_gradient' => 'from-orange-500 via-amber-600 to-indigo-950',
                'thumbnail' => '/images/ref/action.jpg',
                'is_active' => true,
                'is_featured' => false,
                'is_trending' => false,
                'is_new_release' => true,
                'rating' => 4.7,
                'plays_count' => 8420,
                'points_per_win' => 160,
                'points_per_draw' => 45,
            ],
            [
                'slug' => 'moto-xtreme-trials',
                'name' => 'Moto Xtreme: Gravity Trials',
                'category' => 'Motorcycle',
                'description' => 'Side-scrolling physics dirtbike stunt racing. Tear across canyon ramps, control mid-air pitch, pull off double backflips over explosive gaps, and stick clean two-wheel landings.',
                'instructions' => "1. Hold W / D or Right Arrow to throttle forward; S / Down Arrow to brake.\n2. While airborne off mega-ramps, use A/D or Left/Right Arrows to pitch your bike for Backflips and Frontflips!\n3. Press SPACE for Nitro Boost and land level on both wheels for a +500 Clean Landing multiplier!",
                'rules' => "• Keep your rider upright—landing upside-down triggers a crash and respawns at the last checkpoint.\n• Completing 360° backflips or frontflips in mid-air refills 50% Nitro and awards stunt points.\n• Beat the course par time to unlock extreme gravity tracks.",
                'controls_summary' => 'W/D: Throttle • A/Left: Lean Back (Backflip) • D/Right: Lean Forward • SPACE: Nitro',
                'min_players' => 2,
                'max_players' => 4,
                'icon' => 'bolt',
                'cover_gradient' => 'from-purple-600 via-fuchsia-600 to-slate-900',
                'thumbnail' => '/images/ref/controller-hd.jpg',
                'is_active' => true,
                'is_featured' => false,
                'is_trending' => true,
                'is_new_release' => false,
                'rating' => 4.8,
                'plays_count' => 10680,
                'points_per_win' => 175,
                'points_per_draw' => 50,
            ],
            [
                'slug' => 'nova-vanguard-3d',
                'name' => 'Nova Vanguard: Starfighter Assault',
                'category' => 'Space Action',
                'description' => 'Futuristic deep-space dogfighter. Pilot the Vanguard X-9 interceptor through asteroid belts, fire twin plasma cannons and homing photon torpedoes, and destroy Dreadnought capital ships.',
                'instructions' => "1. Pilot your starfighter with WASD / Arrow keys or smooth Mouse tracking.\n2. Hold SPACE or Left-Click to fire Twin Plasma Cannons; press E or Right-Click to launch Homing Photon Torpedoes.\n3. Collect Weapon Overdrive cores to upgrade to Triple-Spread and Hyper-Beam lasers!",
                'rules' => "• Destroy enemy interceptor squadrons, asteroid hazards, and sector Dreadnought bosses.\n• Energy shields absorb hits and recharge after 4 seconds out of fire.\n• Defeating a Dreadnought advances your fleet to the next galactic sector.",
                'controls_summary' => 'WASD / Mouse: Pilot Ship • SPACE / Click: Plasma Cannons • E: Homing Torpedo',
                'min_players' => 2,
                'max_players' => 4,
                'icon' => 'rocket',
                'cover_gradient' => 'from-cyan-500 via-blue-700 to-slate-950',
                'thumbnail' => '/images/ref/setup-hd.jpg',
                'is_active' => true,
                'is_featured' => true,
                'is_trending' => false,
                'is_new_release' => true,
                'rating' => 4.9,
                'plays_count' => 11920,
                'points_per_win' => 200,
                'points_per_draw' => 60,
            ],
            [
                'slug' => 'arena-tag-royale',
                'name' => 'Cyber Tag: Crown Royale',
                'category' => 'Multiplayer',
                'description' => '4-player arena battle royale! Seize the Golden Cyber-Crown, launch off neon jump pads, dash-tackle rivals to steal the crown, and trigger EMP shockwaves to defend your lead.',
                'instructions' => "1. Move and jump across the multi-tier arena platforms using WASD or Arrow keys.\n2. Press SPACE or J to Dash-Tackle a rival and steal the Golden Crown!\n3. Press K or E to emit an EMP Shockwave that knocks back nearby challengers while you earn +25 pts/sec holding the Crown!",
                'rules' => "• Whoever holds the Golden Crown earns continuous points every second.\n• Dash-tackling the crown carrier transfers the crown to you and grants a +150 Steal Bonus.\n• First competitor to reach 1,000 Crown Points (or highest score when time expires) wins the Royale!",
                'controls_summary' => 'WASD / Arrows: Run & Jump • SPACE / J: Dash Tackle • K / E: EMP Shockwave',
                'min_players' => 2,
                'max_players' => 4,
                'icon' => 'crown',
                'cover_gradient' => 'from-violet-600 via-purple-700 to-indigo-950',
                'thumbnail' => '/images/ref/mascot-logo.jpg',
                'is_active' => true,
                'is_featured' => false,
                'is_trending' => true,
                'is_new_release' => true,
                'rating' => 4.8,
                'plays_count' => 9540,
                'points_per_win' => 190,
                'points_per_draw' => 55,
            ],
            [
                'slug' => 'prism-breaker-overdrive',
                'name' => 'Prism Breaker: Neon Overdrive',
                'category' => 'Arcade',
                'description' => 'High-speed kinetic arcade brick-breaker with boss battles! Deflect hyper-velocity plasma spheres, catch Multi-Ball & Dual Laser Turret capsules, and shatter fortified Cyber-Cores.',
                'instructions' => "1. Slide your magnetic deflection paddle using Mouse movement or A/D / Left/Right Arrow keys.\n2. Click or press SPACE to launch the plasma sphere and fire mounted Dual Laser Turrets.\n3. Catch falling Power-Up Capsules (Multi-Ball, Mega-Ball, Laser Cannons, Wide Paddle, Shield Floor)!",
                'rules' => "• Shatter all neon prism blocks and defeat the central Sentinel Core to clear each stage.\n• Angle your deflection hits on the edges of the paddle for high-speed spin shots.\n• Chaining block destructions without paddle bounce increases your Overdrive Multiplier up to 8x!",
                'controls_summary' => 'Mouse / A,D / Arrows: Move Paddle • Click / SPACE: Launch & Fire Lasers',
                'min_players' => 2,
                'max_players' => 2,
                'icon' => 'sparkles',
                'cover_gradient' => 'from-pink-600 via-purple-600 to-cyan-600',
                'thumbnail' => '/images/ref/treasure.jpg',
                'is_active' => true,
                'is_featured' => false,
                'is_trending' => false,
                'is_new_release' => false,
                'rating' => 4.7,
                'plays_count' => 8910,
                'points_per_win' => 150,
                'points_per_draw' => 45,
            ],
        ];

        $seededGames = [];
        foreach ($gamesCatalog as $gameData) {
            $seededGames[$gameData['slug']] = Game::updateOrCreate(
                ['slug' => $gameData['slug']],
                $gameData
            );
        }

        // 2. Seed Action-Packed Achievements
        Achievement::whereIn('slug', [
            'tactical-grid-master',
            'connect-four-architect',
            'grandmaster-prodigy',
        ])->delete();

        $achievementsData = [
            [
                'name' => 'First Blood',
                'slug' => 'first-blood',
                'description' => 'Complete and win your very first action run or arena match.',
                'icon' => 'trophy',
                'badge_color' => 'amber',
                'points_reward' => 50,
                'criteria_type' => 'games_won',
                'criteria_value' => 1,
                'game_id' => null,
            ],
            [
                'name' => 'Arena Challenger',
                'slug' => 'arena-challenger',
                'description' => 'Complete 5 action runs or multiplayer matches across any game.',
                'icon' => 'gamepad',
                'badge_color' => 'cyan',
                'points_reward' => 75,
                'criteria_type' => 'games_played',
                'criteria_value' => 5,
                'game_id' => null,
            ],
            [
                'name' => 'Unstoppable Force',
                'slug' => 'unstoppable-force',
                'description' => 'Achieve a 3-game winning streak in the arena.',
                'icon' => 'flame',
                'badge_color' => 'purple',
                'points_reward' => 125,
                'criteria_type' => 'win_streak',
                'criteria_value' => 3,
                'game_id' => null,
            ],
            [
                'name' => 'Apex Legend',
                'slug' => 'centurion',
                'description' => 'Accumulate 500 or more total Arena XP rating points.',
                'icon' => 'star',
                'badge_color' => 'amber',
                'points_reward' => 150,
                'criteria_type' => 'total_points',
                'criteria_value' => 500,
                'game_id' => null,
            ],
            [
                'name' => 'Squad Commander',
                'slug' => 'squad-leader',
                'description' => 'Connect with at least 2 friends on Nexus Arena.',
                'icon' => 'users',
                'badge_color' => 'emerald',
                'points_reward' => 60,
                'criteria_type' => 'friends_count',
                'criteria_value' => 2,
                'game_id' => null,
            ],
            [
                'name' => 'Sharpshooter Elite',
                'slug' => 'sharpshooter-elite',
                'description' => 'Win 2 tactical missions in Cyber Strike: Tactical Ops.',
                'icon' => 'crosshair',
                'badge_color' => 'cyan',
                'points_reward' => 100,
                'criteria_type' => 'game_specific_wins',
                'criteria_value' => 2,
                'game_id' => $seededGames['cyber-strike-fps']->id,
            ],
            [
                'name' => 'Speed Demon 300+',
                'slug' => 'speed-demon-gt',
                'description' => 'Win a high-speed street race in Nitro Apex GT.',
                'icon' => 'bolt',
                'badge_color' => 'purple',
                'points_reward' => 120,
                'criteria_type' => 'game_specific_wins',
                'criteria_value' => 1,
                'game_id' => $seededGames['nitro-apex-gt']->id,
            ],
            [
                'name' => 'Dragon Fist Grandmaster',
                'slug' => 'dragon-fist-grandmaster',
                'description' => 'Claim victory in Shadow Dojo: Iron Fist.',
                'icon' => 'flame',
                'badge_color' => 'amber',
                'points_reward' => 150,
                'criteria_type' => 'game_specific_wins',
                'criteria_value' => 1,
                'game_id' => $seededGames['shadow-dojo-brawl']->id,
            ],
        ];

        foreach ($achievementsData as $ach) {
            Achievement::updateOrCreate(['slug' => $ach['slug']], $ach);
        }

        // 3. Seed Demo Admin and Players
        $defaultPassword = Hash::make('password123');

        $admin = User::updateOrCreate(
            ['email' => 'admin@nexusarena.gg'],
            [
                'name' => 'Commander Marcus Vance',
                'username' => 'nexus_admin',
                'password' => $defaultPassword,
                'role' => 'admin',
                'bio' => 'Platform Architect & Lead Tournament Commissioner at Nexus Arena.',
                'is_online' => true,
                'last_seen_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $viper = User::updateOrCreate(
            ['email' => 'viper@nexusarena.gg'],
            [
                'name' => 'Elena Rostova',
                'username' => 'cyber_viper',
                'password' => $defaultPassword,
                'role' => 'player',
                'bio' => 'Top-ranked Cyber Strike FPS & Nitro Apex GT champion. Catch me on the leaderboards.',
                'is_online' => true,
                'last_seen_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $nova = User::updateOrCreate(
            ['email' => 'nova@nexusarena.gg'],
            [
                'name' => 'Kai Takahashi',
                'username' => 'astral_nova',
                'password' => $defaultPassword,
                'role' => 'player',
                'bio' => 'Shadow Dojo combo specialist & Nova Vanguard starfighter ace.',
                'is_online' => true,
                'last_seen_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $titan = User::updateOrCreate(
            ['email' => 'titan@nexusarena.gg'],
            [
                'name' => 'Darius Sterling',
                'username' => 'iron_titan',
                'password' => $defaultPassword,
                'role' => 'player',
                'bio' => 'Deadzone Zombie survival veteran & Moto Xtreme stunt rider.',
                'is_online' => false,
                'last_seen_at' => now()->subHours(2),
                'email_verified_at' => now(),
            ]
        );

        $valkyrie = User::updateOrCreate(
            ['email' => 'valkyrie@nexusarena.gg'],
            [
                'name' => 'Freya Lindqvist',
                'username' => 'neon_valkyrie',
                'password' => $defaultPassword,
                'role' => 'player',
                'bio' => 'Emberfall RPG speedrunner & Cyber Tag Crown Royale contender.',
                'is_online' => true,
                'last_seen_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $allDemoUsers = [$admin, $viper, $nova, $titan, $valkyrie];
        foreach ($allDemoUsers as $u) {
            PlayerStatistic::firstOrCreate(
                ['user_id' => $u->id, 'game_id' => null],
                [
                    'games_played' => 0,
                    'games_won' => 0,
                    'games_lost' => 0,
                    'games_drawn' => 0,
                    'win_percentage' => 0,
                    'total_points' => 0,
                ]
            );
        }

        // 4. Seed Friendships & Pending Requests
        $friendPairs = [
            [$viper->id, $nova->id],
            [$viper->id, $valkyrie->id],
            [$nova->id, $titan->id],
        ];

        foreach ($friendPairs as [$u1, $u2]) {
            Friendship::firstOrCreate(['user_id' => $u1, 'friend_id' => $u2]);
            Friendship::firstOrCreate(['user_id' => $u2, 'friend_id' => $u1]);
        }

        FriendRequest::updateOrCreate(
            ['sender_id' => $titan->id, 'receiver_id' => $viper->id],
            ['status' => 'pending']
        );

        // 5. Simulate Completed Action Matches & Populate Authentic Statistics + Leaderboards
        $roomService = app(RoomService::class);
        $scoringService = app(ScoringService::class);

        if (GameSession::count() === 0) {
            // Match 1: Viper beats Nova in Cyber Strike: Tactical Ops
            $r1 = $roomService->createRoom($viper, $seededGames['cyber-strike-fps'], [
                'name' => 'Neo-Tokyo Sniper Showdown',
                'visibility' => 'public',
            ]);
            $roomService->joinRoom($r1, $nova);
            $roomService->submitMove($r1, $viper, ['score' => 3450, 'level' => 4, 'finished' => true, 'event' => 'Cleared Wave 4 Boss']);
            $roomService->submitMove($r1, $nova, ['score' => 2890, 'level' => 3, 'finished' => true, 'event' => 'Eliminated on Wave 3']);

            // Match 2: Viper beats Valkyrie in Nitro Apex GT
            $r2 = $roomService->createRoom($viper, $seededGames['nitro-apex-gt'], [
                'name' => 'Midnight Highway 320KMH Sprint',
                'visibility' => 'public',
            ]);
            $roomService->joinRoom($r2, $valkyrie);
            $roomService->submitMove($r2, $viper, ['score' => 4120, 'level' => 5, 'finished' => true, 'event' => 'Finished Sector 5']);
            $roomService->submitMove($r2, $valkyrie, ['score' => 3600, 'level' => 4, 'finished' => true, 'event' => 'Finished Sector 4']);

            // Match 3: Nova beats Titan in Shadow Dojo: Iron Fist
            $r3 = $roomService->createRoom($nova, $seededGames['shadow-dojo-brawl'], [
                'name' => 'Iron Fist Grand Tournament',
                'visibility' => 'public',
            ]);
            $roomService->joinRoom($r3, $titan);
            $roomService->submitMove($r3, $nova, ['score' => 2950, 'level' => 3, 'finished' => true, 'event' => 'KO Victory Round 3']);
            $roomService->submitMove($r3, $titan, ['score' => 1980, 'level' => 2, 'finished' => true, 'event' => 'Defeated in Round 3']);

            // Match 4: Valkyrie beats Titan in Deadzone: Outbreak 2099
            $r4 = $roomService->createRoom($valkyrie, $seededGames['deadzone-outbreak'], [
                'name' => 'Quarantine Sector Survival',
                'visibility' => 'public',
            ]);
            $roomService->joinRoom($r4, $titan);
            $roomService->submitMove($r4, $valkyrie, ['score' => 3800, 'level' => 5, 'finished' => true, 'event' => 'Survived Blood Moon']);
            $roomService->submitMove($r4, $titan, ['score' => 2400, 'level' => 3, 'finished' => true, 'event' => 'Overrun on Wave 3']);

            // Record solo arcade runs & favorites for demo users
            $scoringService->recordSoloRunScore($viper, $seededGames['cyber-strike-fps'], 4850, 5, true);
            $scoringService->recordSoloRunScore($viper, $seededGames['nova-vanguard-3d'], 3920, 4, true);
            $scoringService->recordSoloRunScore($nova, $seededGames['shadow-dojo-brawl'], 4100, 4, true);
            $scoringService->recordSoloRunScore($valkyrie, $seededGames['emberfall-survivors'], 3650, 4, true);

            UserGameActivity::where('user_id', $viper->id)
                ->whereIn('game_id', [
                    $seededGames['cyber-strike-fps']->id,
                    $seededGames['nitro-apex-gt']->id,
                    $seededGames['nova-vanguard-3d']->id,
                ])
                ->update(['is_favorite' => true]);

            // Create 3 Open Waiting Rooms for immediate multiplayer testing in Lobby
            $roomService->createRoom($nova, $seededGames['cyber-strike-fps'], [
                'name' => 'Cyber Strike Tactical Squad #1',
                'visibility' => 'public',
            ]);
            $roomService->createRoom($valkyrie, $seededGames['nitro-apex-gt'], [
                'name' => 'Tokyo Highway Drift Lobby',
                'visibility' => 'public',
            ]);
            $roomService->createRoom($viper, $seededGames['arena-tag-royale'], [
                'name' => 'Crown Royale 4P Arena',
                'visibility' => 'public',
            ]);

            $scoringService->recalculateRankings();
        }

        // 6. Seed Sample Moderation Report
        Report::firstOrCreate(
            ['reporter_id' => $nova->id, 'reported_user_id' => $titan->id],
            [
                'reason' => 'Unsportsmanlike chat in tournament lobby',
                'details' => 'Player spammed macros after losing the final round in Shadow Dojo.',
                'status' => 'open',
            ]
        );
    }
}
