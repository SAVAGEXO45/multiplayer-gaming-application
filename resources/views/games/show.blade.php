<x-layouts.app :title="$game->name . ' — Play 60FPS Action Game'">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 space-y-8"
         x-data="{
            arcade: null,
            hud: {
                state: 'start',
                score: 0,
                highScore: {{ (int) ($myActivity?->high_score ?? 0) }},
                level: 1,
                health: 100,
                shield: 100,
                ammo: 30,
                combo: 0
            },
            muted: false,
            showTouchControls: false,
            favorite: {{ ($myActivity?->is_favorite ?? false) ? 'true' : 'false' }},
            feedbackToast: null,
            init() {
                // Mount the real HTML5 Canvas + Web Audio Game Engine
                if (window.NexusArcadeEngine) {
                    this.arcade = window.NexusArcadeEngine.mount({
                        canvasId: 'nexus-arcade-canvas',
                        gameSlug: @js($game->slug),
                        gameName: @js($game->name),
                        initialHighScore: {{ (int) ($myActivity?->high_score ?? 0) }},
                        scoreUrl: @auth @js(route('games.score', $game->slug)) @else null @endauth,
                        csrfToken: document.querySelector('meta[name=csrf-token]')?.content || '',
                        onScoreSaved: (payload) => {
                            if (payload?.message) {
                                this.feedbackToast = payload.message;
                                setTimeout(() => this.feedbackToast = null, 4000);
                            }
                        }
                    });
                }

                // Detect touch devices
                if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                    this.showTouchControls = true;
                }

                window.addEventListener('nexus-hud-update', (e) => {
                    if (e.detail) {
                        this.hud = { ...this.hud, ...e.detail };
                    }
                });
            },
            toggleAudio() {
                if (this.arcade) {
                    this.muted = this.arcade.toggleMute();
                }
            },
            toggleFullscreen() {
                if (this.arcade) {
                    this.arcade.toggleFullscreen();
                }
            },
            pauseGame() {
                if (this.arcade) {
                    this.arcade.togglePause();
                }
            },
            restartGame() {
                if (this.arcade) {
                    this.arcade.startGame();
                }
            },
            touchAction(code, isDown) {
                if (this.arcade) {
                    this.arcade.pressVirtualKey(code, isDown);
                }
            },
            toggleFavorite() {
                fetch(@js(route('games.favorite', $game->slug)), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        this.favorite = data.is_favorite;
                        this.feedbackToast = data.message;
                        setTimeout(() => this.feedbackToast = null, 3500);
                    }
                })
                .catch(() => {});
            }
         }">

        <!-- Real-Time Score Feedback Toast -->
        <div x-show="feedbackToast" x-cloak
             class="fixed top-20 right-5 z-50 px-4 py-3 rounded-xl bg-[#090C14] border-2 border-[#FF9900] shadow-2xl text-xs font-bold text-[#FF9900] flex items-center gap-2">
            <span>⚡</span>
            <span x-text="feedbackToast"></span>
        </div>

        <!-- =========================================================================
             1. THE LIVE 60FPS INTERACTIVE GAMEPLAY STAGE
             ========================================================================= -->
        <div class="surface-card rounded-2xl overflow-hidden border border-white/[0.12] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.85)] arcade-stage-wrapper">
            <!-- Game Stage Top Bar Controls -->
            <div class="bg-[#080B14] px-4 py-3 border-b border-white/[0.08] flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase bg-[#FF9900] text-slate-950">
                        60 FPS ENGINE
                    </span>
                    <h1 class="font-display text-base sm:text-lg font-black text-white truncate">{{ $game->name }}</h1>
                    <span class="hidden sm:inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-sky-500/15 text-sky-300 border border-sky-500/30">
                        {{ $game->category }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Toggle Mobile Touch Controls Button -->
                    <button type="button"
                            @click="showTouchControls = !showTouchControls"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-[#121829] hover:bg-[#1A223B] text-slate-300 border border-white/[0.1] transition cursor-pointer"
                            :class="showTouchControls ? 'border-sky-400 text-sky-300' : ''"
                            title="Toggle On-Screen Touch Controls">
                        📱 Touch Controls
                    </button>

                    <!-- Pause Button -->
                    <button type="button"
                            @click="pauseGame()"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-[#121829] hover:bg-[#1A223B] text-slate-300 border border-white/[0.1] transition cursor-pointer"
                            title="Pause / Resume (P)">
                        <span x-text="hud.state === 'paused' ? '▶ Resume' : '⏸ Pause'"></span>
                    </button>

                    <!-- Restart Button -->
                    <button type="button"
                            @click="restartGame()"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-[#121829] hover:bg-[#1A223B] text-slate-300 border border-white/[0.1] transition cursor-pointer"
                            title="Restart (R)">
                        ↻ Restart
                    </button>

                    <!-- Audio Mute Button -->
                    <button type="button"
                            @click="toggleAudio()"
                            class="p-2 rounded-lg bg-[#121829] hover:bg-[#1A223B] text-slate-300 border border-white/[0.1] transition cursor-pointer"
                            :title="muted ? 'Unmute Audio' : 'Mute Audio'">
                        <span x-text="muted ? '🔇' : '🔊'"></span>
                    </button>

                    <!-- Fullscreen Button -->
                    <button type="button"
                            @click="toggleFullscreen()"
                            class="p-2 rounded-lg bg-[#121829] hover:bg-[#1A223B] text-slate-300 border border-white/[0.1] transition cursor-pointer"
                            title="Toggle Fullscreen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- The HTML5 Game Canvas Stage (Aspect Ratio 16:9) -->
            <div class="relative w-full aspect-video bg-[#05070E] overflow-hidden select-none">
                <canvas id="nexus-arcade-canvas"
                        class="w-full h-full block cursor-crosshair">
                </canvas>

                <!-- On-Screen Virtual Touch Controls (Visible on mobile/touch screens or via toggle) -->
                <div x-show="showTouchControls" x-cloak
                     class="pointer-events-none absolute inset-0 p-4 flex items-end justify-between z-20">
                    <!-- Left Virtual D-Pad -->
                    <div class="pointer-events-auto grid grid-cols-3 gap-1.5 w-36 h-36">
                        <div></div>
                        <button type="button"
                                @touchstart.prevent="touchAction('ArrowUp', true)"
                                @touchend.prevent="touchAction('ArrowUp', false)"
                                @mousedown="touchAction('ArrowUp', true)"
                                @mouseup="touchAction('ArrowUp', false)"
                                class="rounded-xl bg-white/20 active:bg-[#FF9900] backdrop-blur-md border border-white/30 text-white font-black text-lg flex items-center justify-center">▲</button>
                        <div></div>

                        <button type="button"
                                @touchstart.prevent="touchAction('ArrowLeft', true)"
                                @touchend.prevent="touchAction('ArrowLeft', false)"
                                @mousedown="touchAction('ArrowLeft', true)"
                                @mouseup="touchAction('ArrowLeft', false)"
                                class="rounded-xl bg-white/20 active:bg-[#FF9900] backdrop-blur-md border border-white/30 text-white font-black text-lg flex items-center justify-center">◀</button>
                        <button type="button"
                                @touchstart.prevent="touchAction('ArrowDown', true)"
                                @touchend.prevent="touchAction('ArrowDown', false)"
                                @mousedown="touchAction('ArrowDown', true)"
                                @mouseup="touchAction('ArrowDown', false)"
                                class="rounded-xl bg-white/20 active:bg-[#FF9900] backdrop-blur-md border border-white/30 text-white font-black text-lg flex items-center justify-center">▼</button>
                        <button type="button"
                                @touchstart.prevent="touchAction('ArrowRight', true)"
                                @touchend.prevent="touchAction('ArrowRight', false)"
                                @mousedown="touchAction('ArrowRight', true)"
                                @mouseup="touchAction('ArrowRight', false)"
                                class="rounded-xl bg-white/20 active:bg-[#FF9900] backdrop-blur-md border border-white/30 text-white font-black text-lg flex items-center justify-center">▶</button>
                    </div>

                    <!-- Right Action Touch Buttons -->
                    <div class="pointer-events-auto flex items-end gap-2.5">
                        <button type="button"
                                @touchstart.prevent="touchAction('ActionSpecial', true)"
                                @touchend.prevent="touchAction('ActionSpecial', false)"
                                @mousedown="touchAction('ActionSpecial', true)"
                                @mouseup="touchAction('ActionSpecial', false)"
                                class="w-16 h-16 rounded-full bg-purple-600/80 active:bg-purple-500 backdrop-blur-md border-2 border-purple-400 text-white font-black text-xs flex flex-col items-center justify-center shadow-lg">
                            <span>SKILL</span>
                            <span class="text-[9px] text-purple-200">SPACE</span>
                        </button>

                        <button type="button"
                                @touchstart.prevent="touchAction('ActionFire', true)"
                                @touchend.prevent="touchAction('ActionFire', false)"
                                @mousedown="touchAction('ActionFire', true)"
                                @mouseup="touchAction('ActionFire', false)"
                                class="w-20 h-20 rounded-full bg-[#FF9900]/90 active:bg-amber-400 backdrop-blur-md border-2 border-white text-slate-950 font-black text-sm flex flex-col items-center justify-center shadow-2xl">
                            <span>FIRE</span>
                            <span class="text-[9px] text-slate-800">CLICK</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Live Telemetry & Control Bar -->
            <div class="bg-[#080B14] p-4 border-t border-white/[0.08] flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4 text-xs font-mono">
                    <span class="text-slate-400">Score: <strong class="text-white text-base" x-text="hud.score.toLocaleString()"></strong></span>
                    <span class="text-slate-400">Personal Record: <strong class="text-[#FF9900] text-base" x-text="hud.highScore.toLocaleString()"></strong></span>
                    <span class="text-slate-400">Sector / Level: <strong class="text-sky-400 text-base" x-text="hud.level"></strong></span>
                    <template x-if="hud.combo > 1">
                        <span class="px-2 py-0.5 rounded bg-orange-500/20 text-[#FF9900] font-bold" x-text="hud.combo + 'x COMBO'"></span>
                    </template>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <button type="button"
                                @click="toggleFavorite()"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border"
                                :class="favorite ? 'bg-rose-500/20 border-rose-500/50 text-rose-300' : 'bg-[#121829] border-white/[0.1] text-slate-300 hover:text-white'">
                            <span x-text="favorite ? '♥ In Favorites' : '♡ Add to Favorites'"></span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="text-xs text-[#FF9900] hover:underline font-bold">
                            Log In to Save Scores &amp; Record High Scores
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        <!-- =========================================================================
             2. GAME OVERVIEW, CONTROLS REFERENCE & INSTRUCTIONS
             ========================================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left 8 Columns: Description, Controls & Official Rules -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Overview Card -->
                <div class="surface-card rounded-2xl p-6 sm:p-8 space-y-4 border border-white/[0.08]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase bg-sky-500/15 text-sky-300 border border-sky-500/30">
                                {{ $game->category }}
                            </span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#0D121F] text-amber-400 border border-amber-500/30">
                                ★ {{ number_format($game->rating, 1) }} Rating
                            </span>
                        </div>
                        <span class="font-mono text-xs text-slate-400">{{ number_format($game->plays_count) }} Total Runs</span>
                    </div>

                    <h2 class="font-display text-2xl font-black text-white">About {{ $game->name }}</h2>
                    <p class="text-sm text-slate-300 leading-relaxed">{{ $game->description }}</p>

                    <!-- Controls Cheat Sheet -->
                    <div class="p-4 rounded-xl bg-[#080B14] border border-white/[0.08] space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#FF9900]">🎮 Controls &amp; Hotkeys Reference</span>
                        <div class="text-xs font-mono text-slate-300 leading-relaxed">
                            {{ $game->controls_summary ?? 'Keyboard & Mouse + Mobile Touchscreen Supported' }}
                        </div>
                    </div>
                </div>

                <!-- Instructions & Rules -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="surface-card rounded-2xl p-6 border border-sky-500/20 space-y-2">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-sky-400">Mission Objectives</h3>
                        <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed">{{ $game->instructions }}</div>
                    </div>

                    <div class="surface-card rounded-2xl p-6 border border-purple-500/20 space-y-2">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-purple-400">Rules &amp; Multipliers</h3>
                        <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed">{{ $game->rules }}</div>
                    </div>
                </div>
            </div>

            <!-- Right 4 Columns: Host Multiplayer Duel & High Score Leaderboard -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Host Multiplayer Duel -->
                <div id="multiplayer" class="surface-card rounded-2xl p-6 border border-white/[0.1] space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        <h3 class="font-display text-base font-bold text-white">Host Multiplayer Room</h3>
                    </div>
                    <p class="text-xs text-slate-400">Challenge friends to a real-time high-score duel in {{ $game->name }}.</p>

                    @auth
                        <form method="POST" action="{{ route('rooms.store') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="game_slug" value="{{ $game->slug }}">
                            <input type="text" name="name" placeholder="Custom Room Title (optional)" maxlength="80"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border border-white/[0.14] text-xs text-white focus:outline-none focus:border-[#FF9900]">
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <label class="flex items-center gap-2 p-2 rounded-xl bg-[#080B12] border border-white/[0.1] cursor-pointer">
                                    <input type="radio" name="visibility" value="public" checked class="text-orange-500">
                                    <span class="text-slate-300 font-medium">Public</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-xl bg-[#080B12] border border-white/[0.1] cursor-pointer">
                                    <input type="radio" name="visibility" value="private" class="text-purple-500">
                                    <span class="text-slate-300 font-medium">Private</span>
                                </label>
                            </div>
                            <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-black btn-start-playing cursor-pointer">
                                Create Room &rarr;
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="w-full py-2.5 rounded-xl text-xs font-bold btn-start-playing text-center block">
                            Login to Host Multiplayer
                        </a>
                    @endauth

                    <!-- Open Waiting Rooms for this game -->
                    @if($publicRooms->isNotEmpty())
                        <div class="pt-3 border-t border-white/[0.08] space-y-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Open Match Lobbies</span>
                            @foreach($publicRooms->take(3) as $r)
                                <div class="p-2.5 rounded-xl bg-[#080B12] border border-white/[0.08] flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-bold text-white">#{{ $r->room_code }}</div>
                                        <div class="text-[10px] text-slate-400">Host: @{{ $r->host->username }}</div>
                                    </div>
                                    <a href="{{ route('rooms.show', $r->room_code) }}" class="px-3 py-1 rounded-lg text-xs font-bold btn-start-playing">
                                        Join
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Game Leaderboard -->
                <div class="surface-card rounded-2xl p-6 border border-white/[0.1] space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-base font-bold text-white">Top {{ $game->name }} Aces</h3>
                        <a href="{{ route('leaderboards.index') }}" class="text-[11px] text-[#FF9900] hover:underline font-bold">All &rarr;</a>
                    </div>

                    <div class="space-y-2">
                        @forelse($topPlayers->take(5) as $idx => $entry)
                            <div class="p-2.5 rounded-xl bg-[#080B12] border border-white/[0.06] flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <span class="font-mono font-bold {{ $idx === 0 ? 'text-[#FF9900]' : 'text-slate-400' }}">#{{ $idx + 1 }}</span>
                                    @if($entry->user)
                                        <img src="{{ $entry->user->avatar_url }}" alt="" class="w-6 h-6 rounded-md object-cover">
                                        <span class="font-bold text-white truncate max-w-[120px]">{{ $entry->user->username }}</span>
                                    @endif
                                </div>
                                <span class="font-mono font-bold text-[#FF9900]">{{ number_format($entry->points) }} pts</span>
                            </div>
                        @empty
                            <div class="text-xs text-slate-400 text-center py-4">No high scores registered yet. Play a run above to take #1!</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             3. MORE ACTION GAMES STRIP
             ========================================================================= -->
        <div class="pt-6 border-t border-white/[0.08] space-y-4">
            <h3 class="font-display text-lg font-black text-white">More Action Titles You Might Like</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach($relatedGames as $rel)
                    <a href="{{ route('games.show', $rel->slug) }}"
                       class="surface-card rounded-xl p-3 border border-white/[0.08] hover:border-[#FF9900] transition group space-y-2">
                        <x-game-artwork :slug="$rel->slug" class="w-full h-28 rounded-lg object-cover" />
                        <div>
                            <span class="text-[10px] uppercase font-bold text-orange-400">{{ $rel->category }}</span>
                            <div class="text-xs font-bold text-white group-hover:text-[#FF9900] transition truncate">{{ $rel->name }}</div>
                            <span class="text-[10px] text-amber-400 font-bold">★ {{ number_format($rel->rating, 1) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
