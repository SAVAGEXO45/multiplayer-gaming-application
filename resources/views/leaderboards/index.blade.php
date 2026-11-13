<x-layouts.app title="Competitive Leaderboards">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-8">
        <!-- Page Header & Period Filter -->
        <div class="relative surface-card rounded-2xl p-6 sm:p-8 overflow-hidden border border-white/[0.12] flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div class="absolute inset-0 pointer-events-none opacity-20">
                <img src="/images/hero-arena.jpg" alt="" class="w-full h-full object-cover object-center" />
                <div class="absolute inset-0 bg-gradient-to-r from-[#0E1320] via-[#0E1320]/90 to-transparent"></div>
            </div>

            <div class="relative">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-xs font-bold uppercase tracking-wider text-amber-300 mb-2.5">
                    👑 Official Standings
                </span>
                <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-white">Competitive Leaderboards</h1>
                <p class="text-sm text-slate-300 mt-1">Server-verified player rankings calculated from match victories, win percentages, and achievement rewards.</p>
            </div>

            <!-- Period Tabs -->
            <div class="relative flex flex-wrap items-center gap-2 bg-[#080B12]/80 p-1.5 rounded-xl border border-white/[0.1]">
                @foreach(['global' => 'All-Time Global', 'weekly' => 'Weekly (' . now()->format('o-\WW') . ')', 'monthly' => 'Monthly (' . now()->format('M Y') . ')'] as $pKey => $pLabel)
                    <a href="{{ route('leaderboards.index', array_filter(['period' => $pKey, 'game' => $selectedGame?->slug])) }}"
                       class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $period === $pKey ? 'btn-flame text-white' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Game Filter Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('leaderboards.index', ['period' => $period]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition {{ !$selectedGame ? 'bg-purple-600 text-white shadow-md' : 'bg-[#121726] text-slate-300 hover:text-white border border-white/[0.1]' }}">
                Overall Platform Ranking
            </a>
            @foreach($games as $game)
                <a href="{{ route('leaderboards.index', ['period' => $period, 'game' => $game->slug]) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $selectedGame?->id === $game->id ? 'bg-purple-600 text-white shadow-md' : 'bg-[#121726] text-slate-300 hover:text-white border border-white/[0.1]' }}">
                    {{ $game->name }}
                </a>
            @endforeach
        </div>

        <!-- Top 3 Podium Cards -->
        @if($entries->count() >= 3)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($entries->take(3) as $idx => $podium)
                    @php
                        $podiumStyle = [
                            0 => [
                                'card' => 'border-amber-500/50 bg-gradient-to-b from-amber-500/[0.12] via-[#121726] to-[#0E1320]',
                                'badge' => 'bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950',
                                'title' => '👑 1ST PLACE',
                                'ptsColor' => 'text-amber-400',
                            ],
                            1 => [
                                'card' => 'border-sky-400/40 bg-gradient-to-b from-sky-500/[0.09] via-[#121726] to-[#0E1320]',
                                'badge' => 'bg-sky-400/20 text-sky-200 border border-sky-400/40',
                                'title' => '🥈 2ND PLACE',
                                'ptsColor' => 'text-sky-300',
                            ],
                            2 => [
                                'card' => 'border-purple-500/40 bg-gradient-to-b from-purple-500/[0.09] via-[#121726] to-[#0E1320]',
                                'badge' => 'bg-purple-500/20 text-purple-200 border border-purple-500/40',
                                'title' => '🥉 3RD PLACE',
                                'ptsColor' => 'text-purple-300',
                            ],
                        ][$idx];
                    @endphp
                    <div class="rounded-2xl p-6 border {{ $podiumStyle['card'] }} shadow-xl">
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 rounded-lg text-[11px] font-extrabold {{ $podiumStyle['badge'] }}">
                                {{ $podiumStyle['title'] }}
                            </span>
                            <span class="font-mono text-lg font-extrabold {{ $podiumStyle['ptsColor'] }}">{{ number_format($podium->points) }} PTS</span>
                        </div>
                        @if($podium->user)
                            <div class="flex items-center gap-3.5">
                                <img src="{{ $podium->user->avatar_url }}" alt="" class="w-14 h-14 rounded-2xl object-cover border-2 border-white/15">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('profile.show', $podium->user->username) }}" class="font-display text-lg font-extrabold text-white hover:text-orange-400 truncate block transition">
                                        {{ $podium->user->name }}
                                    </a>
                                    <div class="text-xs text-slate-400 truncate">@{{ $podium->user->username }}</div>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-white/[0.08] grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-slate-400 block">Wins</span>
                                    <span class="font-mono font-bold text-emerald-400">{{ $podium->wins }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Matches</span>
                                    <span class="font-mono font-bold text-slate-200">{{ $podium->games_played }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-400 block">Win Rate</span>
                                    <span class="font-mono font-bold text-orange-400">{{ number_format($podium->win_percentage, 1) }}%</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Standings Table -->
        <div class="surface-card rounded-2xl overflow-hidden border border-white/[0.1]">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/[0.08] bg-[#0B0F1A] text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-4 px-5">Rank</th>
                            <th class="py-4 px-5">Player</th>
                            <th class="py-4 px-5">Matches Played</th>
                            <th class="py-4 px-5">W / L / D</th>
                            <th class="py-4 px-5">Win Rate</th>
                            <th class="py-4 px-5 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06] text-sm">
                        @forelse($entries as $entry)
                            <tr class="hover:bg-white/[0.03] transition {{ auth()->id() === $entry->user_id ? 'bg-orange-500/[0.05]' : '' }}">
                                <td class="py-3.5 px-5 font-mono font-bold">
                                    <span class="{{ $entry->rank <= 3 ? 'text-amber-400' : 'text-slate-400' }}">#{{ $entry->rank }}</span>
                                </td>
                                <td class="py-3.5 px-5">
                                    @if($entry->user)
                                        <a href="{{ route('profile.show', $entry->user->username) }}" class="flex items-center gap-3 group">
                                            <div class="relative">
                                                <img src="{{ $entry->user->avatar_url }}" alt="" class="w-8 h-8 rounded-lg object-cover">
                                                <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full {{ $entry->user->is_online ? 'bg-emerald-400' : 'bg-slate-600' }} ring-2 ring-[#121726]"></span>
                                            </div>
                                            <div>
                                                <div class="font-bold text-white group-hover:text-orange-400 transition">{{ $entry->user->username }}</div>
                                                <div class="text-xs text-slate-400">{{ $entry->user->name }}</div>
                                            </div>
                                        </a>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 font-mono text-xs text-slate-300">{{ $entry->games_played }}</td>
                                <td class="py-3.5 px-5 text-xs font-mono">
                                    <span class="text-emerald-400 font-bold">{{ $entry->wins }}W</span> /
                                    <span class="text-rose-400">{{ $entry->losses }}L</span> /
                                    <span class="text-slate-400">{{ $entry->draws }}D</span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-24 h-2 bg-[#080B12] rounded-full overflow-hidden border border-white/10">
                                            <div class="h-full bg-gradient-to-r from-orange-500 via-purple-500 to-sky-400" style="width: {{ min(100, $entry->win_percentage) }}%"></div>
                                        </div>
                                        <span class="text-xs font-mono text-slate-200">{{ number_format($entry->win_percentage, 1) }}%</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono font-extrabold text-white">
                                    {{ number_format($entry->points) }} <span class="text-xs font-normal text-orange-400">PTS</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-sm text-slate-400">
                                    No leaderboard entries recorded for this period yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
