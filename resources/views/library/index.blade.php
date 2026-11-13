<x-layouts.app title="My Game Library & Favorites — NexusArena">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-10">
        <!-- Library Header -->
        <div class="surface-card rounded-2xl p-6 sm:p-8 border border-white/[0.1] flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-16 h-16 rounded-2xl object-cover border-2 border-[#FF9900]">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#FF9900]">Personal Vault</span>
                    <h1 class="font-display text-2xl sm:text-3xl font-black text-white">
                        {{ $user->username }}'s Game Library
                    </h1>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Your favorited action games, personal high scores, sector achievements and recent play history.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('games.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-extrabold btn-start-playing">
                    Browse All Games &rarr;
                </a>
            </div>
        </div>

        <!-- 1. FAVORITE ACTION GAMES -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-rose-500 text-base">♥</span>
                    <h2 class="font-display text-xl font-black text-white">Favorite Action Games ({{ $favoriteGames->count() }})</h2>
                </div>
                <a href="{{ route('games.index') }}" class="text-xs font-bold text-[#FF9900] hover:underline">
                    Add More Favorites &rarr;
                </a>
            </div>

            @if($favoriteGames->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($favoriteGames as $activity)
                        @php
                            $g = $activity->game;
                        @endphp
                        @if($g)
                            <div class="surface-card rounded-2xl overflow-hidden border border-white/[0.1] hover:border-rose-500/50 transition flex flex-col justify-between group">
                                <div>
                                    <div class="relative h-48 overflow-hidden border-b border-white/[0.08]">
                                        <x-game-artwork :slug="$g->slug" class="w-full h-full" />
                                        <div class="absolute top-3 left-3">
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase bg-[#05070E]/85 backdrop-blur-md text-sky-300 border border-sky-500/30">
                                                {{ $g->category }}
                                            </span>
                                        </div>
                                        <div class="absolute top-3 right-3">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500 text-white">
                                                ♥ Favorite
                                            </span>
                                        </div>
                                        <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-[11px]">
                                            <span class="px-2 py-0.5 rounded bg-[#05070E]/90 text-amber-300 font-mono font-bold text-[10px] border border-amber-500/30">
                                                Record: {{ number_format($activity->high_score) }} pts
                                            </span>
                                            <span class="px-2 py-0.5 rounded bg-[#05070E]/90 text-sky-300 font-mono font-bold text-[10px] border border-sky-500/30">
                                                Level {{ $activity->max_level }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-5 space-y-2">
                                        <h3 class="font-display text-lg font-bold text-white group-hover:text-[#FF9900] transition">{{ $g->name }}</h3>
                                        <p class="text-xs text-slate-300 line-clamp-2">{{ $g->description }}</p>
                                    </div>
                                </div>

                                <div class="p-5 pt-0 flex items-center gap-3">
                                    <a href="{{ route('games.show', $g->slug) }}"
                                       class="flex-1 py-2.5 rounded-xl text-xs font-black btn-start-playing text-center">
                                        ▶ PLAY NOW
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="surface-card rounded-2xl p-10 text-center space-y-3 border border-white/[0.08]">
                    <div class="text-3xl">♡</div>
                    <h3 class="font-display text-base font-bold text-white">No Favorite Games Yet</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Click the heart icon on any action game in the catalog to pin it here for quick access.
                    </p>
                    <a href="{{ route('games.index') }}" class="inline-block px-5 py-2.5 rounded-xl text-xs font-extrabold btn-start-playing">
                        Explore Action Games
                    </a>
                </div>
            @endif
        </div>

        <!-- 2. RECENTLY PLAYED SESSIONS -->
        <div class="space-y-4 pt-4 border-t border-white/[0.08]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    <h2 class="font-display text-xl font-black text-white">Recently Played</h2>
                </div>
            </div>

            @if($recentlyPlayed->isNotEmpty())
                <div class="surface-card rounded-2xl overflow-hidden border border-white/[0.08]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/[0.08] bg-[#0A0E1A] text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3.5 px-5">Game</th>
                                <th class="py-3.5 px-5">Category</th>
                                <th class="py-3.5 px-5">Your Record</th>
                                <th class="py-3.5 px-5">Max Sector</th>
                                <th class="py-3.5 px-5">Times Played</th>
                                <th class="py-3.5 px-5">Last Played</th>
                                <th class="py-3.5 px-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.06] text-xs">
                            @foreach($recentlyPlayed as $act)
                                @if($act->game)
                                    <tr class="hover:bg-white/[0.02] transition">
                                        <td class="py-3 px-5">
                                            <div class="flex items-center gap-3">
                                                <x-game-artwork :slug="$act->game->slug" class="w-10 h-10 rounded-lg shrink-0 object-cover" />
                                                <span class="font-bold text-white">{{ $act->game->name }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-5 text-slate-300">{{ $act->game->category }}</td>
                                        <td class="py-3 px-5 font-mono text-[#FF9900] font-bold">{{ number_format($act->high_score) }} pts</td>
                                        <td class="py-3 px-5 font-mono text-sky-400">Level {{ $act->max_level }}</td>
                                        <td class="py-3 px-5 font-mono text-slate-300">{{ number_format($act->plays_count) }}</td>
                                        <td class="py-3 px-5 text-slate-400">{{ $act->last_played_at?->diffForHumans() }}</td>
                                        <td class="py-3 px-5 text-right">
                                            <a href="{{ route('games.show', $act->game->slug) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold btn-start-playing">
                                                Play &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="surface-card rounded-2xl p-8 text-center text-xs text-slate-400 border border-white/[0.08]">
                    You haven't played any action games yet. Launch a title from the catalog above to start recording scores!
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
