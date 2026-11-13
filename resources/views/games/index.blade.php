<x-layouts.app title="Action Games Catalog — NexusArena">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-8"
         x-data="{
            toggleFav(slug, btn) {
                fetch(`/games/${slug}/favorite`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        btn.dataset.fav = data.is_favorite ? 'true' : 'false';
                    }
                })
                .catch(() => {});
            }
         }">

        <!-- Catalog Header & Search Filter Bar -->
        <div class="surface-card rounded-2xl p-6 sm:p-8 border border-white/[0.1] space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-widest text-[#FF9900]">🎮 100% Playable 60FPS Action Games</span>
                    <h1 class="font-display text-3xl sm:text-4xl font-black italic text-white mt-1">
                        ACTION GAMES <span class="text-[#FF9900]">CATALOG</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                        Every game features interactive physics, custom synthesized sound effects, keyboard/mouse &amp; mobile virtual touch controls, high-score tracking, and live leaderboards.
                    </p>
                </div>

                <!-- Search Input Form -->
                <form action="{{ route('games.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                    @if($category)
                        <input type="hidden" name="category" value="{{ $category }}">
                    @endif
                    <div class="relative flex-1 sm:w-72">
                        <input type="text"
                               name="q"
                               value="{{ $search ?? '' }}"
                               placeholder="Search title, genre, gameplay..."
                               class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-[#080B14] border border-white/[0.12] text-xs text-white placeholder-slate-400 focus:outline-none focus:border-[#FF9900]">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="sort" onchange="this.form.submit()"
                            class="px-3 py-2.5 rounded-xl bg-[#080B14] border border-white/[0.12] text-xs text-slate-300 focus:outline-none focus:border-[#FF9900]">
                        <option value="featured" {{ ($sort ?? '') === 'featured' ? 'selected' : '' }}>Sort: Featured</option>
                        <option value="trending" {{ ($sort ?? '') === 'trending' ? 'selected' : '' }}>Sort: Trending</option>
                        <option value="new" {{ ($sort ?? '') === 'new' ? 'selected' : '' }}>Sort: New Releases</option>
                        <option value="rating" {{ ($sort ?? '') === 'rating' ? 'selected' : '' }}>Sort: Highest Rated</option>
                        <option value="popular" {{ ($sort ?? '') === 'popular' ? 'selected' : '' }}>Sort: Most Played</option>
                    </select>

                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-extrabold btn-start-playing">
                        Search
                    </button>
                    @if($search || $category)
                        <a href="{{ route('games.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-semibold bg-[#121624] text-slate-400 hover:text-white">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Category Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 border-t border-white/[0.08] pt-4">
                <a href="{{ route('games.index', array_filter(['q' => $search, 'sort' => $sort])) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ !$category ? 'bg-[#FF9900] text-slate-950 font-black' : 'bg-[#0E1322] text-slate-300 hover:text-white border border-white/[0.08]' }}">
                    All Genres
                </a>
                @foreach($allCategories as $cat)
                    <a href="{{ route('games.index', array_filter(['category' => $cat, 'q' => $search, 'sort' => $sort])) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ $category === $cat ? 'bg-[#FF9900] text-slate-950 font-black' : 'bg-[#0E1322] text-slate-300 hover:text-white border border-white/[0.08]' }}">
                        {{ $cat }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Games Grid (All 10 Real Playable Action Games) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($games as $game)
                <div class="surface-card rounded-2xl overflow-hidden border border-white/[0.1] hover:border-[#FF9900]/50 transition flex flex-col justify-between group">
                    <div>
                        <!-- Game Artwork with Favorite Toggle & Badges -->
                        <div class="relative h-56 overflow-hidden border-b border-white/[0.08]">
                            <x-game-artwork :slug="$game->slug" class="w-full h-full" />

                            <div class="absolute top-3 left-3 flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase bg-[#05070E]/85 backdrop-blur-md text-sky-300 border border-sky-500/30">
                                    {{ $game->category }}
                                </span>
                                @if($game->is_trending)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-orange-500 text-slate-950">
                                        🔥 Trending
                                    </span>
                                @endif
                                @if($game->is_new_release)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-sky-500 text-slate-950">
                                        ⚡ New
                                    </span>
                                @endif
                            </div>

                            <div class="absolute top-3 right-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#05070E]/85 backdrop-blur-md text-amber-400 border border-amber-500/30">
                                    ★ {{ number_format($game->rating, 1) }}
                                </span>

                                @auth
                                    <button type="button"
                                            @click="toggleFav('{{ $game->slug }}', $el)"
                                            data-fav="{{ $game->is_favorited_by_user ? 'true' : 'false' }}"
                                            class="w-8 h-8 rounded-full bg-[#05070E]/85 backdrop-blur-md flex items-center justify-center border border-white/20 transition cursor-pointer group-hover:scale-105"
                                            title="Add to Favorites">
                                        <svg class="w-4 h-4 transition text-rose-500"
                                             fill="currentColor"
                                             viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                    </button>
                                @endauth
                            </div>

                            <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-[11px]">
                                <span class="px-2 py-0.5 rounded bg-[#05070E]/90 text-slate-300 font-mono text-[10px] border border-white/10">
                                    {{ number_format($game->plays_count) }} Plays
                                </span>
                                @if(($game->user_high_score ?? 0) > 0)
                                    <span class="px-2 py-0.5 rounded bg-[#05070E]/90 text-amber-300 font-mono font-bold text-[10px] border border-amber-500/30">
                                        Best: {{ number_format($game->user_high_score) }} pts
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-[#05070E]/90 text-[#FF9900] font-mono font-bold text-[10px] border border-orange-500/30">
                                        +{{ $game->points_per_win }} XP Win
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-3">
                            <div>
                                <h2 class="font-display text-xl font-extrabold text-white group-hover:text-[#FF9900] transition">
                                    {{ $game->name }}
                                </h2>
                                <p class="text-xs text-slate-300 mt-1 leading-relaxed line-clamp-2">
                                    {{ $game->description }}
                                </p>
                            </div>

                            @if($game->controls_summary)
                                <div class="p-2.5 rounded-xl bg-[#090C16] border border-white/[0.06] text-[11px] font-mono text-slate-400">
                                    <span class="text-[#FF9900] font-bold">Controls:</span> {{ $game->controls_summary }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 pt-0 flex items-center gap-3">
                        <a href="{{ route('games.show', $game->slug) }}"
                           class="flex-1 py-3 rounded-xl text-xs font-black btn-start-playing flex items-center justify-center gap-2">
                            <span>▶ PLAY NOW</span>
                        </a>
                        <a href="{{ route('games.show', $game->slug) }}#multiplayer"
                           class="px-4 py-3 rounded-xl text-xs font-bold bg-[#121624] hover:bg-[#1A2033] text-sky-300 border border-sky-500/30 transition">
                            Rooms ({{ $game->active_rooms_count }})
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full surface-card rounded-2xl p-12 text-center space-y-3">
                    <p class="text-base text-slate-300 font-bold">No games matched your search filter.</p>
                    <a href="{{ route('games.index') }}" class="inline-block px-5 py-2.5 rounded-xl text-xs font-extrabold btn-start-playing">
                        Reset All Filters
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
