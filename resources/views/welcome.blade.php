<x-layouts.app title="NEXUSARENA — Play Action Games, Compete & Win">
    <!-- =========================================================================
         1. REFERENCE BLUEPRINT 3-ROW HERO & ACTION GAMING GALLERY
         ========================================================================= -->
    <section class="pt-4 pb-8 px-3 sm:px-6 lg:px-8 max-w-[1400px] mx-auto">
        <div class="rounded-2xl overflow-hidden border border-white/[0.12] bg-[#05070E] shadow-[0_25px_70px_-15px_rgba(0,0,0,0.9)] space-y-1.5 p-1.5">

            <!-- ROW 1: HERO BANNER ("PLAY. COMPETE. WIN.") + DUALSENSE CONTROLLER + PRO SETUP -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-1.5">
                <!-- Panel 1 (Top-Left, 5 cols): PLAY. COMPETE. WIN. Hero Banner -->
                <div class="lg:col-span-5 relative min-h-[300px] sm:min-h-[350px] rounded-xl overflow-hidden bg-[#070910] flex items-center group">
                    <!-- Masked Cyber-Warrior Character Artwork -->
                    <img src="/images/ref/hero-warrior-hd.jpg"
                         alt="Play Compete Win Hero Character"
                         class="absolute inset-0 w-full h-full object-cover object-right group-hover:scale-105 transition duration-700" />
                    <div class="absolute inset-0 bg-gradient-to-r from-[#06080F] via-[#06080F]/75 to-transparent"></div>

                    <!-- Diagonal Orange Accent Stripes -->
                    <div class="pointer-events-none absolute -top-6 -left-10 w-36 h-16 bg-gradient-to-r from-[#FF6B00] to-[#FFA000] -rotate-45 opacity-95 shadow-lg"></div>
                    <div class="pointer-events-none absolute top-4 -left-12 w-28 h-2 bg-[#FF9900] -rotate-45 opacity-80"></div>

                    <!-- Hero Typography & Direct Launch Action Button -->
                    <div class="relative z-10 p-6 sm:p-8 max-w-[70%] space-y-3.5">
                        <h1 class="hero-headline-italic tracking-tight drop-shadow-[0_4px_16px_rgba(0,0,0,0.8)]">
                            <span class="block text-white text-3xl sm:text-4xl xl:text-[44px]">PLAY.</span>
                            <span class="block text-white text-3xl sm:text-4xl xl:text-[44px]">COMPETE.</span>
                            <span class="block text-[#FF9900] text-4xl sm:text-5xl xl:text-[56px] mt-0.5">WIN.</span>
                        </h1>

                        <p class="text-xs sm:text-sm font-medium text-slate-200 tracking-wide leading-relaxed">
                            Your Ultimate Gaming Destination • 10 Genuinely Playable Action Games
                        </p>

                        <div class="pt-1.5 flex flex-wrap gap-2.5">
                            <a href="{{ route('games.show', 'cyber-strike-fps') }}"
                               class="inline-flex items-center gap-2 px-6 py-3 rounded-full text-xs sm:text-sm font-extrabold btn-start-playing shadow-lg shadow-orange-500/25">
                                <span>▶ START PLAYING</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Panel 2 (Top-Center, 3 cols): Precision DualSense Controller -->
                <a href="{{ route('games.show', 'nitro-apex-gt') }}"
                   class="lg:col-span-3 relative min-h-[240px] sm:min-h-[350px] rounded-xl overflow-hidden bg-[#05070E] group block">
                    <img src="/images/ref/controller-hd.jpg"
                         alt="Next-Gen Gaming Controller"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-700" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/90 via-transparent to-transparent"></div>
                    <div class="absolute bottom-3.5 left-4 right-4 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-sky-400 block">60FPS Street Racing</span>
                            <span class="font-display text-sm font-extrabold text-white">Nitro Apex GT: 320 KM/H</span>
                        </div>
                        <span class="w-8 h-8 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center text-white group-hover:bg-[#FF9900] group-hover:text-slate-950 transition">
                            &rarr;
                        </span>
                    </div>
                </a>

                <!-- Panel 3 (Top-Right, 4 cols): Pro Gaming Battlestation & Live Telemetry -->
                <a href="{{ route('games.show', 'shadow-dojo-brawl') }}"
                   class="lg:col-span-4 relative min-h-[240px] sm:min-h-[350px] rounded-xl overflow-hidden bg-[#080914] group block">
                    <img src="/images/ref/setup-hd.jpg"
                         alt="Pro Gaming Setup"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-700" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/90 via-transparent to-transparent"></div>

                    <!-- "GAME ON" Wall Poster Badge -->
                    <div class="absolute top-4 right-4 px-3 py-1.5 rounded-lg bg-[#120924]/90 border border-purple-500/50 shadow-[0_0_20px_rgba(168,85,247,0.45)]">
                        <span class="hero-headline-italic text-xs tracking-wider text-rose-400 block leading-none">GAME</span>
                        <span class="hero-headline-italic text-sm tracking-wider text-sky-400 block leading-none">ON</span>
                    </div>

                    <div class="absolute bottom-3.5 left-4 right-4 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-purple-300 block">Martial Arts Tournament</span>
                            <span class="font-display text-sm font-extrabold text-white">Shadow Dojo: Iron Fist</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            {{ number_format($stats['online_players']) }} Online
                        </span>
                    </div>
                </a>
            </div>

            <!-- ROW 2: 4-PANEL ACTION GAMES VISUAL GALLERY -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-1.5">
                <!-- Col 1: Sports / Street Hoops 3x3 -->
                <a href="{{ route('games.show', 'street-hoops-pro') }}"
                   class="relative h-52 sm:h-60 rounded-xl overflow-hidden group block bg-[#0B1324]">
                    <img src="/images/ref/action.jpg"
                         alt="Street Hoops Pro 3x3"
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/90 via-transparent to-transparent opacity-85 group-hover:opacity-95 transition"></div>
                    <div class="absolute bottom-3 left-3.5 right-3.5 flex items-end justify-between">
                        <div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-orange-500/25 text-orange-300 border border-orange-400/30">Sports</span>
                            <h3 class="font-display text-base font-extrabold text-white mt-1">Street Hoops 3x3</h3>
                            <span class="text-[11px] text-slate-300 block">Timing Meter &bull; Flaming Streaks</span>
                        </div>
                        <span class="text-xs font-bold text-[#FF9900] group-hover:translate-x-1 transition">&rarr;</span>
                    </div>
                </a>

                <!-- Col 2: Tactical FPS Shooter -->
                <a href="{{ route('games.show', 'cyber-strike-fps') }}"
                   class="relative h-52 sm:h-60 rounded-xl overflow-hidden group block bg-[#121318]">
                    <img src="/images/ref/shooter.jpg"
                         alt="Cyber Strike Tactical Ops FPS"
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/90 via-transparent to-transparent opacity-85 group-hover:opacity-95 transition"></div>
                    <div class="absolute bottom-3 left-3.5 right-3.5 flex items-end justify-between">
                        <div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-red-500/25 text-red-300 border border-red-400/30">Shooting FPS</span>
                            <h3 class="font-display text-base font-extrabold text-white mt-1">Cyber Strike: Ops</h3>
                            <span class="text-[11px] text-slate-300 block">Aim &bull; Headshots &bull; Boss Waves</span>
                        </div>
                        <span class="text-xs font-bold text-[#FF9900] group-hover:translate-x-1 transition">&rarr;</span>
                    </div>
                </a>

                <!-- Col 3: Adventure & Survival RPG -->
                <a href="{{ route('games.show', 'emberfall-survivors') }}"
                   class="relative h-52 sm:h-60 rounded-xl overflow-hidden group block bg-[#101824]">
                    <img src="/images/ref/sandbox.jpg"
                         alt="Emberfall Blade and Ruin Adventure"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/90 via-transparent to-transparent opacity-85 group-hover:opacity-95 transition"></div>
                    <div class="absolute bottom-3 left-3.5 right-3.5 flex items-end justify-between">
                        <div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/25 text-amber-300 border border-amber-400/30">Adventure RPG</span>
                            <h3 class="font-display text-base font-extrabold text-white mt-1">Emberfall: Blade</h3>
                            <span class="text-[11px] text-slate-300 block">Sword Slash &bull; Chests &bull; Level Up</span>
                        </div>
                        <span class="text-xs font-bold text-[#FF9900] group-hover:translate-x-1 transition">&rarr;</span>
                    </div>
                </a>

                <!-- Col 4: Supercar Highway Racing -->
                <a href="{{ route('games.show', 'nitro-apex-gt') }}"
                   class="relative h-52 sm:h-60 rounded-xl overflow-hidden group block bg-[#140D26]">
                    <img src="/images/ref/racing.jpg"
                         alt="Nitro Apex GT Highway Racing"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/90 via-transparent to-transparent opacity-85 group-hover:opacity-95 transition"></div>
                    <div class="absolute bottom-3 left-3.5 right-3.5 flex items-end justify-between">
                        <div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-sky-500/25 text-sky-300 border border-sky-400/30">Car Racing</span>
                            <h3 class="font-display text-base font-extrabold text-white mt-1">Nitro Apex GT</h3>
                            <span class="text-[11px] text-slate-300 block">Nitro Boost &bull; Traffic Near-Misses</span>
                        </div>
                        <span class="text-xs font-bold text-[#FF9900] group-hover:translate-x-1 transition">&rarr;</span>
                    </div>
                </a>
            </div>

            <!-- ROW 3: 4-PANEL ZOMBIE, MOTORCYCLE, SPACE & MULTIPLAYER ARTWORK -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-1.5">
                <!-- Col 1: Zombie Survival (Deadzone) -->
                <a href="{{ route('games.show', 'deadzone-outbreak') }}"
                   class="relative h-44 sm:h-48 rounded-xl overflow-hidden group block bg-[#090B16]">
                    <img src="/images/games/hero-arena.jpg"
                         alt="Deadzone Outbreak Zombie Survival"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/85 via-transparent to-transparent"></div>
                    <div class="absolute bottom-2.5 left-3.5 right-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-400">Zombie Survival</span>
                            <div class="text-xs font-bold text-white">Deadzone: Outbreak</div>
                        </div>
                        <span class="text-xs text-[#FF9900] font-bold">Play &rarr;</span>
                    </div>
                </a>

                <!-- Col 2: Motorcycle Gravity Stunt Trials -->
                <a href="{{ route('games.show', 'moto-xtreme-trials') }}"
                   class="relative h-44 sm:h-48 rounded-xl overflow-hidden group block bg-[#070912]">
                    <img src="/images/ref/controller-hd.jpg"
                         alt="Moto Xtreme Gravity Trials"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/85 via-transparent to-transparent"></div>
                    <div class="absolute bottom-2.5 left-3.5 right-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-300">Motorcycle</span>
                            <div class="text-xs font-bold text-white">Moto Xtreme Trials</div>
                        </div>
                        <span class="text-xs text-[#FF9900] font-bold">Play &rarr;</span>
                    </div>
                </a>

                <!-- Col 3: Kinetic Arcade Prism Breaker -->
                <a href="{{ route('games.show', 'prism-breaker-overdrive') }}"
                   class="relative h-44 sm:h-48 rounded-xl overflow-hidden group block bg-[#120E0A]">
                    <img src="/images/ref/treasure.jpg"
                         alt="Prism Breaker Neon Overdrive"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/85 via-transparent to-transparent"></div>
                    <div class="absolute bottom-2.5 left-3.5 right-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-pink-400">Challenging Arcade</span>
                            <div class="text-xs font-bold text-white">Prism Breaker</div>
                        </div>
                        <span class="text-xs text-[#FF9900] font-bold">Play &rarr;</span>
                    </div>
                </a>

                <!-- Col 4: Multiplayer Crown Royale -->
                <a href="{{ route('games.show', 'arena-tag-royale') }}"
                   class="relative h-44 sm:h-48 rounded-xl overflow-hidden group block bg-[#060814]">
                    <img src="/images/ref/mascot-logo.jpg"
                         alt="Cyber Tag Crown Royale"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05070E]/85 via-transparent to-transparent"></div>
                    <div class="absolute bottom-2.5 left-3.5 right-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-sky-300">Multiplayer Royale</span>
                            <div class="text-xs font-bold text-white">Cyber Tag Royale</div>
                        </div>
                        <span class="text-xs text-[#FF9900] font-bold">Play &rarr;</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         2. USER'S RECENTLY PLAYED & FAVORITES STRIP (Authenticated Players)
         ========================================================================= -->
    @auth
        @if($recentlyPlayedGames->isNotEmpty() || $favoriteGames->isNotEmpty())
            <section class="py-6 border-y border-white/[0.08] bg-[#070A14]">
                <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FF9900]"></span>
                            <h2 class="font-display text-sm font-extrabold uppercase tracking-wider text-white">
                                Your Game Library &bull; Jump Back In
                            </h2>
                        </div>
                        <a href="{{ route('library.index') }}" class="text-xs font-bold text-[#FF9900] hover:underline">
                            Open Full Library &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        @foreach($recentlyPlayedGames->take(6) as $recent)
                            <a href="{{ route('games.show', $recent->slug) }}"
                               class="surface-card rounded-xl p-3 border border-white/[0.08] hover:border-[#FF9900] transition group flex items-center gap-3">
                                <x-game-artwork :slug="$recent->slug" class="w-12 h-12 rounded-lg shrink-0 object-cover" />
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-white truncate group-hover:text-[#FF9900] transition">{{ $recent->name }}</div>
                                    <span class="text-[10px] text-slate-400 block">{{ $recent->category }}</span>
                                    <span class="text-[10px] text-emerald-400 font-mono font-bold">Resume &bull; 60FPS</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endauth

    <!-- =========================================================================
         3. FEATURED ACTION GAMES (Direct Play Now Launchers)
         ========================================================================= -->
    <section class="py-14 max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <span class="text-xs font-extrabold uppercase tracking-widest text-[#FF9900]">Instant-Play Action Titles</span>
                <h2 class="font-display text-3xl sm:text-4xl font-black italic uppercase text-white mt-1">
                    FEATURED <span class="text-[#FF9900]">ACTION GAMES</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">Every game features authentic gameplay mechanics, keyboard/mouse &amp; touch controls, audio SFX, and live score sync.</p>
            </div>
            <a href="{{ route('games.index') }}"
               class="px-5 py-2.5 rounded-xl text-xs font-bold bg-[#0E1322] hover:bg-[#171F36] text-white border border-white/[0.12] transition self-start sm:self-auto">
                Explore All 10 Games &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($featuredGames as $game)
                <div class="surface-card rounded-2xl overflow-hidden border border-white/[0.1] hover:border-[#FF9900]/50 transition flex flex-col justify-between group">
                    <div>
                        <!-- Cover Art with Badge Overlays -->
                        <div class="relative h-52 overflow-hidden border-b border-white/[0.08]">
                            <x-game-artwork :slug="$game->slug" class="w-full h-full" />
                            <div class="absolute top-3 left-3 flex items-center gap-1.5">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase bg-[#05070E]/85 backdrop-blur-md text-sky-300 border border-sky-500/30">
                                    {{ $game->category }}
                                </span>
                            </div>
                            <div class="absolute top-3 right-3 flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#05070E]/85 backdrop-blur-md text-amber-400 border border-amber-500/30">
                                    ★ {{ number_format($game->rating, 1) }}
                                </span>
                            </div>
                            <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-[11px]">
                                <span class="px-2 py-0.5 rounded bg-[#05070E]/90 text-slate-300 font-mono text-[10px] border border-white/10">
                                    {{ number_format($game->plays_count) }} Plays
                                </span>
                                <span class="px-2 py-0.5 rounded bg-[#05070E]/90 text-[#FF9900] font-mono font-bold text-[10px] border border-orange-500/30">
                                    +{{ $game->points_per_win }} XP
                                </span>
                            </div>
                        </div>

                        <!-- Info Body -->
                        <div class="p-5 space-y-2">
                            <h3 class="font-display text-lg font-extrabold text-white group-hover:text-[#FF9900] transition">
                                {{ $game->name }}
                            </h3>
                            <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                                {{ $game->description }}
                            </p>
                            @if($game->controls_summary)
                                <div class="pt-2 text-[10px] font-mono text-slate-400 truncate">
                                    {{ $game->controls_summary }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 pt-0">
                        <a href="{{ route('games.show', $game->slug) }}"
                           class="w-full py-2.5 rounded-xl text-xs font-extrabold btn-start-playing flex items-center justify-center gap-2">
                            <span>▶ PLAY NOW</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- =========================================================================
         4. TRENDING & NEW RELEASES SPLIT SECTION
         ========================================================================= -->
    <section class="py-14 border-t border-white/[0.08] bg-[#070A14]">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <!-- Trending Row -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-[#FF9900]">🔥 Highest Community Activity</span>
                        <h2 class="font-display text-2xl sm:text-3xl font-black text-white mt-0.5">Trending Games</h2>
                    </div>
                    <a href="{{ route('games.index', ['sort' => 'trending']) }}" class="text-xs font-bold text-sky-400 hover:underline">
                        View All Trending &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($trendingGames->take(3) as $game)
                        <div class="surface-card rounded-2xl p-4 border border-white/[0.08] flex items-center gap-4 hover:border-orange-500/40 transition group">
                            <x-game-artwork :slug="$game->slug" class="w-24 h-24 rounded-xl shrink-0 object-cover" />
                            <div class="min-w-0 flex-1 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-orange-400">{{ $game->category }}</span>
                                <h3 class="font-display text-sm font-extrabold text-white truncate group-hover:text-[#FF9900] transition">{{ $game->name }}</h3>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                    <span class="text-amber-400 font-bold">★ {{ number_format($game->rating, 1) }}</span>
                                    <span>&bull;</span>
                                    <span>{{ number_format($game->plays_count) }} plays</span>
                                </div>
                                <div class="pt-1">
                                    <a href="{{ route('games.show', $game->slug) }}" class="inline-block text-xs font-bold text-[#FF9900] hover:underline">
                                        Launch Game &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- New Releases Row -->
            <div class="space-y-6 pt-6 border-t border-white/[0.06]">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-sky-400">⚡ Freshly Released In Arena</span>
                        <h2 class="font-display text-2xl sm:text-3xl font-black text-white mt-0.5">New Action Releases</h2>
                    </div>
                    <a href="{{ route('games.index', ['sort' => 'new']) }}" class="text-xs font-bold text-sky-400 hover:underline">
                        View All New &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($newReleases->take(3) as $game)
                        <div class="surface-card rounded-2xl p-4 border border-white/[0.08] flex items-center gap-4 hover:border-sky-500/40 transition group">
                            <x-game-artwork :slug="$game->slug" class="w-24 h-24 rounded-xl shrink-0 object-cover" />
                            <div class="min-w-0 flex-1 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-sky-400">{{ $game->category }}</span>
                                <h3 class="font-display text-sm font-extrabold text-white truncate group-hover:text-sky-300 transition">{{ $game->name }}</h3>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                    <span class="text-amber-400 font-bold">★ {{ number_format($game->rating, 1) }}</span>
                                    <span>&bull;</span>
                                    <span class="text-emerald-400 font-semibold">New Release</span>
                                </div>
                                <div class="pt-1">
                                    <a href="{{ route('games.show', $game->slug) }}" class="inline-block text-xs font-bold text-sky-400 hover:underline">
                                        Launch Game &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         5. GAME CATEGORIES BROWSER (#categories)
         ========================================================================= -->
    <section id="categories" class="py-14 border-t border-white/[0.08]">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex items-end justify-between">
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-widest text-[#FF9900]">Filter By Genre</span>
                    <h2 class="font-display text-3xl font-black italic uppercase text-white mt-1">
                        GAME <span class="text-[#FF9900]">CATEGORIES</span>
                    </h2>
                </div>
                <a href="{{ route('games.index') }}" class="text-xs font-bold text-slate-300 hover:text-white">
                    All Categories &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach($categories as $cat)
                    <a href="{{ route('games.index', ['category' => $cat['name']]) }}"
                       class="surface-card rounded-2xl p-4 border border-white/[0.08] hover:border-[#FF9900] transition group text-center space-y-2">
                        <div class="w-12 h-12 rounded-xl bg-[#0D111D] border border-white/[0.1] mx-auto flex items-center justify-center text-lg group-hover:scale-110 transition">
                            @switch($cat['name'])
                                @case('Shooting') 🔫 @break
                                @case('Car Racing') 🏎️ @break
                                @case('Fighting') 🥷 @break
                                @case('Adventure') ⚔️ @break
                                @case('Zombie Survival') 🧟 @break
                                @case('Sports') 🏀 @break
                                @case('Motorcycle') 🏍️ @break
                                @case('Space Action') 🚀 @break
                                @case('Multiplayer') 🏆 @break
                                @case('Arcade') 🧩 @break
                                @default 🎮
                            @endswitch
                        </div>
                        <h3 class="font-display text-sm font-extrabold text-white group-hover:text-[#FF9900] transition">{{ $cat['name'] }}</h3>
                        <span class="text-[11px] text-slate-400 block">{{ $cat['count'] }} {{ Str::plural('Game', $cat['count']) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- =========================================================================
         6. MULTIPLAYER ARENA ROOMS & LEADERBOARDS
         ========================================================================= -->
    <section class="py-14 border-t border-white/[0.08] bg-[#070A14]">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <!-- Live Multiplayer Rooms -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">Real-Time Multiplayer Rooms</span>
                        <h2 class="font-display text-2xl sm:text-3xl font-black text-white mt-0.5">Live Match Lobbies</h2>
                    </div>
                    <a href="{{ route('rooms.index') }}" class="px-4 py-2 rounded-xl text-xs font-extrabold btn-start-playing">
                        Browse All Rooms ({{ $stats['active_rooms'] }}) &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @forelse($activeRooms as $room)
                        @php
                            $occupied = $room->roomPlayers->whereIn('status', ['active', 'ready'])->count();
                        @endphp
                        <div class="surface-card rounded-2xl p-5 border border-white/[0.08] flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-bold text-sky-400">#{{ $room->room_code }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $room->status === 'waiting' ? 'bg-orange-500/15 text-orange-300 border border-orange-500/30' : 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' }}">
                                        {{ $room->status === 'waiting' ? 'Waiting' : 'In Progress' }}
                                    </span>
                                </div>
                                <h3 class="font-display text-base font-bold text-white mt-2">{{ $room->name }}</h3>
                                <div class="text-xs text-slate-400 mt-1">Game: <strong class="text-slate-200">{{ $room->game->name }}</strong></div>
                                <div class="text-xs text-slate-400">Host: @{{ $room->host->username }}</div>
                            </div>
                            <div class="pt-3 border-t border-white/[0.08] flex items-center justify-between">
                                <span class="font-mono text-xs text-slate-300">{{ $occupied }}/{{ $room->max_players }} Players</span>
                                <a href="{{ route('rooms.show', $room->room_code) }}"
                                   class="px-4 py-1.5 rounded-lg text-xs font-bold btn-start-playing">
                                    Join Room &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full surface-card rounded-2xl p-8 text-center text-xs text-slate-400">
                            No open public rooms waiting right now. Create a lobby for any action title!
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Global High-Score Leaderboard -->
            <div class="space-y-6 pt-6 border-t border-white/[0.08]">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-[#FF9900]">Competitive Rankings</span>
                        <h2 class="font-display text-2xl sm:text-3xl font-black text-white mt-0.5">Top Players Leaderboard</h2>
                    </div>
                    <a href="{{ route('leaderboards.index') }}" class="text-xs font-bold text-[#FF9900] hover:underline">
                        Full Leaderboard &rarr;
                    </a>
                </div>

                <div class="surface-card rounded-2xl overflow-hidden border border-white/[0.08]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/[0.08] bg-[#0A0E1A] text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3.5 px-5">Rank</th>
                                <th class="py-3.5 px-5">Player</th>
                                <th class="py-3.5 px-5">Wins</th>
                                <th class="py-3.5 px-5">Win Rate</th>
                                <th class="py-3.5 px-5 text-right">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.06] text-sm">
                            @foreach($topPlayers as $idx => $entry)
                                <tr class="hover:bg-white/[0.02] transition">
                                    <td class="py-3 px-5 font-mono font-bold {{ $idx < 3 ? 'text-[#FF9900]' : 'text-slate-400' }}">
                                        #{{ $idx + 1 }}
                                    </td>
                                    <td class="py-3 px-5">
                                        @if($entry->user)
                                            <a href="{{ route('profile.show', $entry->user->username) }}" class="flex items-center gap-3 group">
                                                <img src="{{ $entry->user->avatar_url }}" alt="{{ $entry->user->username }}" class="w-8 h-8 rounded-lg object-cover">
                                                <div>
                                                    <div class="font-bold text-white group-hover:text-[#FF9900] transition">{{ $entry->user->username }}</div>
                                                    <div class="text-[11px] text-slate-400">{{ $entry->user->name }}</div>
                                                </div>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="py-3 px-5 font-mono text-emerald-400 font-semibold">{{ $entry->wins }}</td>
                                    <td class="py-3 px-5 font-mono text-slate-300">{{ number_format($entry->win_percentage, 1) }}%</td>
                                    <td class="py-3 px-5 text-right font-mono font-bold text-white">
                                        {{ number_format($entry->points) }} <span class="text-xs text-[#FF9900]">XP</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
