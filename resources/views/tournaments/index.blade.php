<x-layouts.app title="Esports Tournaments & Seasonal Cups">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-10">
        <!-- Header -->
        <div class="relative surface-card rounded-2xl p-6 sm:p-8 overflow-hidden border border-white/[0.12] flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="absolute inset-0 pointer-events-none opacity-25">
                <img src="/images/hero-arena.jpg" alt="" class="w-full h-full object-cover object-center" />
                <div class="absolute inset-0 bg-gradient-to-r from-[#0E1320] via-[#0E1320]/90 to-transparent"></div>
            </div>

            <div class="relative space-y-2.5 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-500/30 text-xs font-bold text-purple-300">
                    🏆 SEASON 01 CHAMPIONSHIP CIRCUIT
                </div>
                <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-white">Competitive Tournaments</h1>
                <p class="text-sm text-slate-300 leading-relaxed">
                    Compete in scheduled NEXUSARENA championship series and daily qualifiers. Every ranked victory in official game rooms contributes directly to your seasonal tournament seeding.
                </p>
            </div>

            <div class="relative flex flex-wrap items-center gap-3 shrink-0">
                <a href="{{ route('rooms.index') }}"
                   class="px-5 py-3 rounded-xl text-xs font-bold btn-flame">
                    Enter Qualifier Rooms
                </a>
                <a href="{{ route('leaderboards.index', ['period' => 'weekly']) }}"
                   class="px-5 py-3 rounded-xl text-xs font-bold bg-[#161D30] hover:bg-[#1E2740] text-slate-200 border border-white/[0.12] transition">
                    Weekly Standings
                </a>
            </div>
        </div>

        <!-- Featured Seasonal Events Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-7">
            @foreach($games as $idx => $game)
                @php
                    $prizePools = ['chess' => '2,500 PTS + Grandmaster Badge', 'connect-four' => '1,500 PTS + Matrix Architect', 'rock-paper-scissors' => '1,200 PTS + Reflex Crown', 'tic-tac-toe' => '1,000 PTS + Tactical Crest'];
                    $schedules = ['chess' => 'Saturdays • 19:00 UTC', 'connect-four' => 'Sundays • 18:00 UTC', 'rock-paper-scissors' => 'Daily Blitz • 20:00 UTC', 'tic-tac-toe' => 'Fridays • 21:00 UTC'];
                @endphp
                <div class="surface-card surface-card-interactive rounded-2xl overflow-hidden flex flex-col justify-between group">
                    <div>
                        <div class="relative h-52 overflow-hidden border-b border-white/[0.08]">
                            <x-game-artwork :slug="$game->slug" class="w-full h-full" />
                            <div class="absolute top-3.5 left-3.5 right-3.5 flex items-center justify-between">
                                <span class="px-3 py-1 rounded-lg text-xs font-bold bg-[#080B12]/85 backdrop-blur-md text-emerald-400 border border-emerald-500/30">
                                    Open Qualifier
                                </span>
                                <span class="px-3 py-1 rounded-lg text-xs font-mono font-bold text-orange-300 bg-[#080B12]/85 backdrop-blur-md border border-orange-500/30">
                                    {{ $schedules[$game->slug] ?? 'Weekly Series' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6 space-y-3">
                            <h2 class="font-display text-2xl font-extrabold text-white group-hover:text-orange-400 transition">
                                {{ $game->name }} Masters Cup
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                                Official 1v1 competitive ladder qualifier for {{ $game->name }}. Win matches in public rooms to qualify for the top-8 bracket finals.
                            </p>

                            <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-[#080B12] border border-white/[0.08] text-xs">
                                <div>
                                    <span class="text-slate-400 block">Reward Pool</span>
                                    <strong class="text-amber-400">{{ $prizePools[$game->slug] ?? '1,000 PTS' }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Active Qualifier Rooms</span>
                                    <strong class="text-sky-400 font-mono">{{ $game->active_rooms_count }} Live</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-6 flex items-center gap-3">
                        <a href="{{ route('games.show', $game->slug) }}"
                           class="flex-1 text-center py-3 rounded-xl text-xs font-bold btn-flame">
                            Compete in {{ $game->name }}
                        </a>
                        <a href="{{ route('leaderboards.index', ['game' => $game->slug]) }}"
                           class="px-4 py-3 rounded-xl text-xs font-bold bg-[#161D30] hover:bg-[#1E2740] text-slate-200 border border-white/[0.1] transition">
                            Seeding
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Current Tournament Seeding (Top 10 Leaderboard) -->
        <div class="surface-card rounded-2xl p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="font-display text-lg font-extrabold text-white">Current Championship Seeding (Top 10)</h2>
                    <p class="text-xs text-slate-400">Live qualification standings based on verified global rating points.</p>
                </div>
                <a href="{{ route('leaderboards.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">
                    Full Standings &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-white/[0.08] text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-3 px-4">Seed</th>
                            <th class="py-3 px-4">Competitor</th>
                            <th class="py-3 px-4">Victories</th>
                            <th class="py-3 px-4">Win Rate</th>
                            <th class="py-3 px-4 text-right">Qualification Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06]">
                        @forelse($topPlayers as $idx => $entry)
                            <tr class="hover:bg-white/[0.03]">
                                <td class="py-3 px-4 font-mono font-bold {{ $idx < 3 ? 'text-amber-400' : 'text-slate-400' }}">
                                    SEED #{{ $idx + 1 }}
                                </td>
                                <td class="py-3 px-4">
                                    @if($entry->user)
                                        <a href="{{ route('profile.show', $entry->user->username) }}" class="flex items-center gap-3">
                                            <img src="{{ $entry->user->avatar_url }}" alt="" class="w-7 h-7 rounded-lg object-cover">
                                            <span class="font-bold text-white hover:text-orange-400">{{ $entry->user->username }}</span>
                                        </a>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-emerald-400 font-bold">{{ $entry->wins }}</td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-300">{{ number_format($entry->win_percentage, 1) }}%</td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-orange-400">{{ number_format($entry->points) }} PTS</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-xs text-slate-400">No qualification matches completed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
