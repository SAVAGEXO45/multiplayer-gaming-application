<x-layouts.app title="Player Dashboard">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-8"
         x-data="{ createRoomModal: false, selectedGameSlug: '{{ $games->first()?->slug ?? 'cyber-strike-fps' }}' }">

        <!-- 1. Profile Hero Banner Card -->
        <div class="relative surface-card rounded-2xl p-6 sm:p-8 overflow-hidden border border-white/[0.12] flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="absolute inset-0 pointer-events-none opacity-20">
                <img src="/images/ref/hero-warrior-hd.jpg" alt="" class="w-full h-full object-cover object-center" />
                <div class="absolute inset-0 bg-gradient-to-r from-[#0E1320] via-[#0E1320]/90 to-transparent"></div>
            </div>

            <div class="relative flex flex-col sm:flex-row sm:items-center gap-5">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 rounded-2xl p-[2px] bg-gradient-to-br from-orange-500 via-purple-600 to-sky-500 shadow-xl">
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-full h-full rounded-[14px] object-cover">
                    </div>
                    <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-400 ring-2 ring-[#0E1320]"></span>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-orange-500/20 text-orange-300 border border-orange-500/30">
                            RANK #{{ $globalStat->rank_position ?? 1 }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                            {{ number_format($globalStat->total_points) }} PTS
                        </span>
                    </div>
                    <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-white mt-1.5">{{ $user->name }}</h1>
                    <p class="text-xs sm:text-sm text-slate-300">@{{ $user->username }} &bull; {{ $user->bio ?: 'Action Challenger on NEXUSARENA' }}</p>
                </div>
            </div>

            <div class="relative flex flex-wrap items-center gap-3">
                <button @click="createRoomModal = true"
                        type="button"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold btn-start-playing cursor-pointer">
                    + Host Game Room
                </button>
                <a href="{{ route('library.index') }}"
                   class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#161D30] hover:bg-[#1E2740] text-amber-300 border border-white/[0.12] transition">
                    My Game Library
                </a>
                <a href="{{ route('profile.show', $user->username) }}"
                   class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#0B0F1A] hover:bg-[#141A2B] text-slate-300 border border-white/[0.1] transition">
                    Profile Settings
                </a>
            </div>
        </div>

        <!-- 2. Gaming Statistics KPI Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="surface-card card-accent-orange rounded-xl p-4">
                <span class="text-xs text-slate-400 font-semibold">Current Ranking</span>
                <div class="font-display text-2xl font-extrabold text-white mt-1">#{{ $globalStat->rank_position ?? 1 }}</div>
                <span class="text-[11px] text-orange-400 font-medium">Global Standing</span>
            </div>

            <div class="surface-card card-accent-purple rounded-xl p-4">
                <span class="text-xs text-slate-400 font-semibold">Arena Points</span>
                <div class="font-display text-2xl font-extrabold text-amber-400 mt-1">{{ number_format($globalStat->total_points) }}</div>
                <span class="text-[11px] text-purple-300">XP Earned</span>
            </div>

            <div class="surface-card card-accent-blue rounded-xl p-4">
                <span class="text-xs text-slate-400 font-semibold">Matches Played</span>
                <div class="font-display text-2xl font-extrabold text-white mt-1">{{ number_format($globalStat->games_played) }}</div>
                <span class="text-[11px] text-slate-400">{{ $globalStat->games_drawn }} draws</span>
            </div>

            <div class="surface-card card-accent-emerald rounded-xl p-4">
                <span class="text-xs text-slate-400 font-semibold">Victories</span>
                <div class="font-display text-2xl font-extrabold text-emerald-400 mt-1">{{ number_format($globalStat->games_won) }}</div>
                <span class="text-[11px] text-slate-400">{{ $globalStat->games_lost }} losses</span>
            </div>

            <div class="surface-card card-accent-blue rounded-xl p-4">
                <span class="text-xs text-slate-400 font-semibold">Win Percentage</span>
                <div class="font-display text-2xl font-extrabold text-white mt-1">{{ number_format($globalStat->win_percentage, 1) }}%</div>
                <div class="w-full h-1.5 bg-[#080B12] rounded-full mt-2 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-orange-500 to-sky-400" style="width: {{ min(100, $globalStat->win_percentage) }}%"></div>
                </div>
            </div>

            <div class="surface-card card-accent-purple rounded-xl p-4">
                <span class="text-xs text-slate-400 font-semibold">Win Streak</span>
                <div class="font-display text-2xl font-extrabold text-purple-400 mt-1">{{ $globalStat->current_streak }}W</div>
                <span class="text-[11px] text-slate-400">Best: {{ $globalStat->best_streak }}W</span>
            </div>
        </div>

        <!-- 3. Favorites and Recent Library Strip -->
        @if($favoriteGames->isNotEmpty() || $recentlyPlayedGames->isNotEmpty())
            <div class="surface-card rounded-2xl p-6 border border-white/[0.08] space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-rose-500 font-bold">♥</span>
                        <h2 class="font-display text-base font-extrabold text-white">Favorite &amp; Recent Games</h2>
                    </div>
                    <a href="{{ route('library.index') }}" class="text-xs font-bold text-[#FF9900] hover:underline">
                        Open Full Library &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($favoriteGames->take(6) as $fav)
                        @if($fav->game)
                            <a href="{{ route('games.show', $fav->game->slug) }}"
                               class="p-2.5 rounded-xl bg-[#080B12] border border-white/[0.08] hover:border-[#FF9900] transition group space-y-1.5 block">
                                <x-game-artwork :slug="$fav->game->slug" class="w-full h-20 rounded-lg object-cover" />
                                <div class="text-xs font-bold text-white group-hover:text-[#FF9900] truncate transition">{{ $fav->game->name }}</div>
                                <span class="text-[10px] text-amber-400 font-mono font-bold block">Best: {{ number_format($fav->high_score) }} pts</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <!-- 4. Main Two-Column Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left 8 Columns: Available Game Rooms, Game Breakdown & Match History -->
            <div class="lg:col-span-8 space-y-8">
                <!-- Available Game Rooms -->
                <div class="surface-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="font-display text-lg font-extrabold text-white">Available Game Rooms</h2>
                            <p class="text-xs text-slate-400">Join an active multiplayer duel or resume your ongoing match.</p>
                        </div>
                        <button @click="createRoomModal = true" class="text-xs font-bold text-orange-400 hover:text-orange-300 cursor-pointer">
                            + Create Room
                        </button>
                    </div>

                    <div class="space-y-3">
                        @forelse($openLobbyRooms as $room)
                            <div class="p-4 rounded-xl bg-[#080B12] border border-white/[0.08] hover:border-purple-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <x-game-artwork :slug="$room->game->slug" class="w-16 h-11 rounded-lg shrink-0 object-cover hidden sm:block" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-sky-500/15 text-sky-300 border border-sky-500/30">
                                                #{{ $room->room_code }}
                                            </span>
                                            <span class="text-sm font-bold text-white truncate">{{ $room->name }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $room->status === 'waiting' ? 'bg-amber-500/15 text-amber-300' : 'bg-emerald-500/15 text-emerald-300' }}">
                                                {{ str_replace('_', ' ', $room->status) }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1">
                                            <span class="text-orange-400 font-medium">{{ $room->game->name }}</span> &bull; Host: <span class="text-slate-200">@{{ $room->host->username }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="font-mono text-xs text-slate-300">
                                        {{ $room->roomPlayers->whereIn('status', ['active', 'ready'])->count() }}/{{ $room->max_players }} Players
                                    </span>
                                    <a href="{{ route('rooms.show', $room->room_code) }}"
                                       class="px-4 py-2 rounded-xl text-xs font-bold btn-start-playing">
                                        {{ $room->hasPlayer($user->id) ? 'Resume Match' : 'Join Room' }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 rounded-xl bg-[#080B12] border border-white/[0.08] text-center text-xs text-slate-400">
                                No active rooms waiting right now. Click "+ Host Game Room" to launch a match.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Per-Game Statistics Breakdown -->
                <div class="surface-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="font-display text-lg font-extrabold text-white">Performance by Action Game</h2>
                            <p class="text-xs text-slate-400">Your win rate and points across each official title.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($games as $game)
                            @php
                                $gStat = $gameStats->get($game->id);
                                $played = $gStat?->games_played ?? 0;
                                $won = $gStat?->games_won ?? 0;
                                $winRate = $gStat?->win_percentage ?? 0;
                                $pts = $gStat?->total_points ?? 0;
                            @endphp
                            <div class="p-4 rounded-xl bg-[#080B12] border border-white/[0.08] space-y-3">
                                <div class="flex items-center gap-3">
                                    <x-game-artwork :slug="$game->slug" class="w-12 h-9 rounded-lg shrink-0 object-cover" />
                                    <div class="flex-1 min-w-0 flex items-center justify-between gap-2">
                                        <a href="{{ route('games.show', $game->slug) }}" class="font-display text-sm font-bold text-white hover:text-orange-400 truncate transition">
                                            {{ $game->name }}
                                        </a>
                                        <span class="font-mono text-xs font-bold text-amber-400 shrink-0">{{ number_format($pts) }} PTS</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-xs text-slate-400">
                                    <span>{{ $played }} Runs/Matches ({{ $won }} Victories)</span>
                                    <span class="font-mono text-slate-200">{{ number_format($winRate, 1) }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-[#121726] rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-orange-500 via-purple-500 to-sky-400" style="width: {{ max(3, min(100, $winRate)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Match History Table -->
                <div class="surface-card rounded-2xl p-6">
                    <h2 class="font-display text-lg font-extrabold text-white mb-4">Match History</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-white/[0.08] text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="py-3 px-3">Game</th>
                                    <th class="py-3 px-3">Room</th>
                                    <th class="py-3 px-3">Result</th>
                                    <th class="py-3 px-3">Summary</th>
                                    <th class="py-3 px-3 text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.06]">
                                @forelse($recentSessions as $session)
                                    @php
                                        $won = !$session->is_draw && (int) $session->winner_user_id === (int) $user->id;
                                    @endphp
                                    <tr class="hover:bg-white/[0.03]">
                                        <td class="py-3 px-3 font-semibold text-white">{{ $session->game->name }}</td>
                                        <td class="py-3 px-3 font-mono text-xs text-sky-400">
                                            <a href="{{ route('rooms.show', $session->room->room_code) }}" class="hover:underline">#{{ $session->room->room_code }}</a>
                                        </td>
                                        <td class="py-3 px-3">
                                            @if($session->is_draw)
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-700/50 text-slate-300">Draw</span>
                                            @elseif($won)
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Victory</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30">Defeat</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 text-xs text-slate-300">{{ $session->result_reason ?? 'Match completed' }}</td>
                                        <td class="py-3 px-3 text-right text-xs text-slate-500">{{ $session->ended_at?->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-xs text-slate-400">No completed matches recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right 4 Columns: Achievements, Recent Activities & Online Friends -->
            <div class="lg:col-span-4 space-y-8">
                <!-- Achievements -->
                <div class="surface-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-display text-base font-extrabold text-white">Achievements</h2>
                        <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                            {{ $unlockedAchievements->count() }} / {{ $totalAchievementsCount }}
                        </span>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($unlockedAchievements as $ach)
                            <div class="p-3 rounded-xl bg-[#080B12] border border-white/[0.08] flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold shrink-0">
                                    ★
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-xs text-white truncate">{{ $ach->name }}</div>
                                    <div class="text-[11px] text-slate-400 truncate">{{ $ach->description }}</div>
                                </div>
                                <span class="font-mono text-xs font-bold text-[#FF9900] shrink-0">+{{ $ach->points_reward }}</span>
                            </div>
                        @empty
                            <div class="text-center py-6 text-xs text-slate-400">
                                No achievements unlocked yet. Play action runs to unlock badges!
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Online Friends -->
                <div class="surface-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-display text-base font-extrabold text-white">Friends &amp; Squad</h2>
                        <a href="{{ route('friends.index') }}" class="text-xs font-bold text-sky-400 hover:text-sky-300">
                            Manage &rarr;
                        </a>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($onlineFriends as $friend)
                            <div class="p-2.5 rounded-xl bg-[#080B12] border border-white/[0.08] flex items-center justify-between">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="relative shrink-0">
                                        <img src="{{ $friend->avatar_url }}" alt="{{ $friend->username }}" class="w-8 h-8 rounded-lg object-cover">
                                        <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full {{ $friend->is_online ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                    </div>
                                    <div class="truncate">
                                        <div class="font-bold text-xs text-white truncate">{{ $friend->username }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $friend->is_online ? 'Online' : 'Offline' }}</div>
                                    </div>
                                </div>
                                <a href="{{ route('profile.show', $friend->username) }}"
                                   class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#161D30] text-slate-300 hover:text-white transition">
                                    Profile
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-6 text-xs text-slate-400">
                                No friends added yet. Connect with competitors in the lobby!
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Host Room Modal -->
        <div x-show="createRoomModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div @click.outside="createRoomModal = false" class="surface-elevated rounded-2xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-bold text-white">Host Action Game Room</h3>
                    <button @click="createRoomModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <form method="POST" action="{{ route('rooms.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Select Game</label>
                        <select name="game_slug" x-model="selectedGameSlug"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border border-white/[0.14] text-xs text-white focus:outline-none focus:border-orange-500">
                            @foreach($games as $g)
                                <option value="{{ $g->slug }}">{{ $g->name }} ({{ $g->category }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Room Title (Optional)</label>
                        <input type="text" name="name" placeholder="e.g. {{ $user->username }}'s Cyber Arena" maxlength="80"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border border-white/[0.14] text-xs text-white focus:outline-none focus:border-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Visibility</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl bg-[#080B12] border border-white/[0.12] cursor-pointer">
                                <input type="radio" name="visibility" value="public" checked class="text-orange-500">
                                <span class="text-slate-200 font-medium">Public</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl bg-[#080B12] border border-white/[0.12] cursor-pointer">
                                <input type="radio" name="visibility" value="private" class="text-purple-500">
                                <span class="text-slate-200 font-medium">Private</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-2">
                        <button type="button" @click="createRoomModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-300 hover:bg-white/[0.05]">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black btn-start-playing cursor-pointer">
                            Create &amp; Enter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
