@php
    $initialPayload = app(\App\Http\Controllers\Web\RoomWebController::class)->buildRoomStatePayload($room, auth()->user());
@endphp
<x-layouts.app :title="$room->name . ' (#' . $room->room_code . ')'">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
         x-data="arenaRoomController(@js($initialPayload), {{ auth()->id() }}, @js($room->game->slug))"
         x-init="initRoom()">

        <!-- Top Room Control Bar -->
        <div class="surface-card rounded-2xl p-5 border border-white/[0.1] flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('games.show', $room->game->slug) }}"
                   class="px-3 py-1.5 rounded-lg bg-[#0D111A] hover:bg-[#161C2B] border border-[#263147] text-xs font-semibold text-slate-300 transition">
                    &larr; {{ $room->game->name }}
                </a>
                <span class="px-2.5 py-1 rounded-md font-mono text-xs font-bold bg-[#161C2B] text-sky-400 border border-[#263147]">
                    ROOM #<span x-text="state.room_code"></span>
                </span>
                <h1 class="font-display text-lg sm:text-xl font-bold text-white" x-text="state.name"></h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase"
                      :class="state.status === 'in_progress' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-300 border border-amber-500/30'"
                      x-text="state.status.replace('_', ' ')"></span>
                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-[#161C2B] text-slate-400 border border-[#263147]" x-text="state.visibility"></span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        @click="copyRoomCode()"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#0D111A] hover:bg-[#161C2B] text-slate-200 border border-[#263147] transition cursor-pointer">
                    <span x-text="copied ? 'Copied #' + state.room_code : 'Copy Code #' + state.room_code"></span>
                </button>

                <template x-if="canStartOrRestart()">
                    <button type="button"
                            @click="startOrRestartMatch()"
                            :disabled="busy"
                            class="px-4 py-2 rounded-xl text-xs font-extrabold btn-start-playing cursor-pointer">
                        <span x-text="state.session && state.session.status === 'completed' ? 'Rematch / Next Round' : 'Start Match'"></span>
                    </button>
                </template>

                <form method="POST" action="{{ route('rooms.leave', $room->room_code) }}">
                    @csrf
                    <button type="submit"
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 border border-rose-500/30 transition cursor-pointer">
                        Leave Room
                    </button>
                </form>
            </div>
        </div>

        <!-- Dynamic Error Banner -->
        <div x-show="errorMessage" x-cloak class="rounded-xl bg-rose-500/15 border border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-200 flex items-center justify-between">
            <span x-text="errorMessage"></span>
            <button @click="errorMessage = null" class="text-rose-300 hover:text-white">&times;</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left 3 Columns: Player Slots & Friend Invites -->
            <div class="lg:col-span-3 space-y-6">
                <div class="surface-card rounded-2xl p-5 space-y-4 border border-white/[0.08]">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-sm font-bold text-white">Player Slots</h2>
                        <span class="font-mono text-xs text-slate-400" x-text="activePlayers().length + ' / ' + state.max_players"></span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="slotNum in [1, 2]" :key="slotNum">
                            <div class="p-3.5 rounded-xl border transition"
                                 :class="getPlayerBySlot(slotNum)
                                    ? 'bg-[#0D111A] border-[#263147]'
                                    : 'bg-[#090C12] border-dashed border-[#1E2738]'">
                                <template x-if="getPlayerBySlot(slotNum)">
                                    <div>
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <img :src="getPlayerBySlot(slotNum).avatar_url" class="w-9 h-9 rounded-lg object-cover border border-[#263147]">
                                                <div class="truncate">
                                                    <div class="text-xs font-bold text-white truncate flex items-center gap-1.5">
                                                        <span x-text="getPlayerBySlot(slotNum).username"></span>
                                                        <template x-if="getPlayerBySlot(slotNum).user_id === state.host_user_id">
                                                            <span class="px-1.5 py-0.2 rounded bg-orange-500/20 text-[9px] text-[#FF9900] uppercase font-bold">Host</span>
                                                        </template>
                                                    </div>
                                                    <div class="text-[11px] text-slate-400">
                                                        Slot #<span x-text="slotNum"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-mono text-base font-bold text-amber-400" x-text="getPlayerBySlot(slotNum).score"></div>
                                                <div class="text-[9px] uppercase text-slate-500">Wins</div>
                                            </div>
                                        </div>

                                        <div class="mt-2.5 flex items-center justify-between text-[10px]">
                                            <span class="uppercase font-semibold text-emerald-400" x-text="getPlayerBySlot(slotNum).status"></span>
                                            <template x-if="getPlayerRun(getPlayerBySlot(slotNum).user_id)">
                                                <span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 font-mono"
                                                      x-text="getPlayerRun(getPlayerBySlot(slotNum).user_id).score + ' pts'"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!getPlayerBySlot(slotNum)">
                                    <div class="py-3 text-center">
                                        <div class="text-xs font-semibold text-slate-500">Slot #<span x-text="slotNum"></span> — Open</div>
                                        <div class="text-[11px] text-slate-600 mt-0.5">Waiting for challenger...</div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    @if(!$room->hasPlayer(auth()->id()) && !$room->isFull())
                        <form method="POST" action="{{ route('rooms.join', $room->room_code) }}">
                            @csrf
                            <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-extrabold btn-start-playing cursor-pointer">
                                Join This Room
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Invite Friend Box -->
                <div class="surface-card rounded-2xl p-5 space-y-3 border border-white/[0.08]">
                    <h3 class="font-display text-sm font-bold text-white">Invite Challenger</h3>
                    <form @submit.prevent="sendInvite()" class="flex gap-2">
                        <input type="text" x-model="inviteUsername" placeholder="Enter username..." required
                               class="flex-1 px-3 py-2 rounded-xl bg-[#090C12] border border-[#263147] text-xs text-white focus:outline-none focus:border-[#FF9900]">
                        <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-extrabold btn-start-playing cursor-pointer">
                            Invite
                        </button>
                    </form>
                    <p x-show="inviteFeedback" x-text="inviteFeedback" class="text-[11px] text-emerald-400 font-medium"></p>

                    @if($friends->isNotEmpty())
                        <div class="pt-2 border-t border-[#1E2738] space-y-1.5">
                            <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Online Friends</span>
                            @foreach($friends->take(4) as $f)
                                <div class="flex items-center justify-between text-xs py-1">
                                    <span class="text-slate-300 truncate">@{{ $f->username }}</span>
                                    <button type="button"
                                            @click="inviteUsername = '{{ $f->username }}'; sendInvite()"
                                            class="px-2 py-0.5 rounded bg-[#161C2B] hover:bg-[#FF9900] text-[10px] font-semibold text-slate-300 hover:text-slate-950 cursor-pointer">
                                        Invite
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Center 6 Columns: Interactive Multiplayer Action Stage -->
            <div class="lg:col-span-6">
                <div class="surface-card rounded-2xl p-6 min-h-[500px] flex flex-col justify-between border border-white/[0.1]">
                    <!-- Turn & Status Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#1E2738]">
                        <div>
                            <div class="text-xs font-mono text-slate-400">
                                <span x-text="state.session ? 'Duel Session #' + state.session.session_number : 'Lobby Standby'"></span>
                            </div>
                            <div class="font-display text-lg font-bold mt-0.5"
                                 :class="statusBannerClass()"
                                 x-text="statusBannerText()"></div>
                        </div>

                        <template x-if="state.session && state.session.status === 'completed'">
                            <button type="button"
                                    @click="startOrRestartMatch()"
                                    class="px-4 py-2 rounded-xl text-xs font-extrabold btn-start-playing cursor-pointer">
                                Rematch &rarr;
                            </button>
                        </template>
                    </div>

                    <!-- Standby State (Waiting for Players or Host to start) -->
                    <template x-if="!state.session">
                        <div class="my-auto py-12 text-center space-y-4">
                            <div class="w-14 h-14 rounded-2xl bg-[#161C2B] border border-white/10 mx-auto flex items-center justify-center text-[#FF9900] text-2xl">
                                ⚔️
                            </div>
                            <h3 class="font-display text-xl font-black text-white">Multiplayer Arena Standby</h3>
                            <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto leading-relaxed">
                                Both competitors play <strong class="text-white">{{ $room->game->name }}</strong> simultaneously. High scores are synchronized live, and the top performer wins the match!
                            </p>
                            <template x-if="activePlayers().length >= 2">
                                <button type="button"
                                        @click="startOrRestartMatch()"
                                        class="px-6 py-2.5 rounded-xl text-xs font-extrabold btn-start-playing cursor-pointer">
                                    ▶ Start Arena Duel
                                </button>
                            </template>
                        </div>
                    </template>

                    <!-- ACTIVE ARENA SESSION: Real HTML5 Canvas Action Game Mounted in Room -->
                    <template x-if="state.session">
                        <div class="my-auto py-4 space-y-4" x-init="$nextTick(() => mountRoomArcade())">
                            <!-- Duel Leader Scoreboard -->
                            <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-[#080B14] border border-white/[0.08]">
                                <template x-for="p in activePlayers()" :key="p.user_id">
                                    <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-[#0E1322]">
                                        <div class="flex items-center gap-2">
                                            <img :src="p.avatar_url" class="w-7 h-7 rounded-md object-cover">
                                            <span class="text-xs font-bold text-white truncate max-w-[90px]" x-text="p.username"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-mono text-sm font-black text-[#FF9900]"
                                                  x-text="((getPlayerRun(p.user_id)?.score || 0)) + ' pts'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Live Action Game Canvas -->
                            <div class="relative w-full aspect-video rounded-xl overflow-hidden bg-[#05070E] border border-white/[0.1] select-none">
                                <canvas id="nexus-room-arcade-canvas" class="w-full h-full block cursor-crosshair"></canvas>
                            </div>

                            <!-- Synchronize Action Buttons -->
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Controls: WASD / Mouse / Touch. Complete your run to submit your duel score.</span>
                                <button type="button"
                                        @click="submitRunFinish()"
                                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#1A223B] text-sky-300 hover:text-white border border-sky-500/30">
                                    Submit Final Run Score
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right 3 Columns: Live Moves & Duel Events -->
            <div class="lg:col-span-3 space-y-6">
                <div class="surface-card rounded-2xl p-5 space-y-4 border border-white/[0.08]">
                    <h3 class="font-display text-sm font-bold text-white">Arena Event Feed</h3>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <template x-for="event in (state.session?.board_state?.events || []).slice().reverse()" :key="event.timestamp">
                            <div class="p-2.5 rounded-xl bg-[#090C12] border border-white/[0.06] text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-white" x-text="'@' + getUsernameById(event.user_id)"></span>
                                    <span class="font-mono text-[10px] text-sky-400" x-text="event.score + ' pts'"></span>
                                </div>
                                <div class="text-[11px] text-slate-400" x-text="event.event"></div>
                            </div>
                        </template>
                        <template x-if="!(state.session?.board_state?.events?.length)">
                            <div class="text-xs text-slate-500 text-center py-6">Events will appear here during the match.</div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function arenaRoomController(initialPayload, currentUserId, gameSlug) {
            return {
                state: initialPayload,
                currentUserId: Number(currentUserId),
                gameSlug: gameSlug,
                busy: false,
                errorMessage: null,
                copied: false,
                inviteUsername: '',
                inviteFeedback: '',
                roomArcade: null,

                initRoom() {
                    if (window.Echo) {
                        try {
                            window.Echo.channel(`game-room.${this.state.room_code}`)
                                .listen('.room.updated', (e) => {
                                    this.state = e.room;
                                })
                                .listen('.game.move', (e) => {
                                    if (e.session) {
                                        this.state.session = e.session;
                                    }
                                });
                        } catch (err) {}
                    }
                },

                mountRoomArcade() {
                    if (this.roomArcade || !window.NexusArcadeEngine) return;
                    const canvas = document.getElementById('nexus-room-arcade-canvas');
                    if (!canvas) return;

                    this.roomArcade = window.NexusArcadeEngine.mount({
                        canvasId: 'nexus-room-arcade-canvas',
                        gameSlug: this.gameSlug,
                        gameName: this.state.game?.name || 'Action Arena',
                        roomMoveUrl: `{{ route('rooms.moves', $room->room_code) }}`,
                        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || ''
                    });
                },

                activePlayers() {
                    return (this.state.players || []).filter(p => p.status === 'active' || p.status === 'ready');
                },

                getPlayerBySlot(slot) {
                    return (this.state.players || []).find(p => Number(p.slot) === Number(slot) && p.status !== 'left') || null;
                },

                getPlayerRun(userId) {
                    return this.state.session?.board_state?.player_runs?.[userId] || null;
                },

                getUsernameById(userId) {
                    const p = (this.state.players || []).find(pl => Number(pl.user_id) === Number(userId));
                    return p ? p.username : 'Competitor';
                },

                canStartOrRestart() {
                    const isMember = (this.state.players || []).some(p => Number(p.user_id) === this.currentUserId && p.status !== 'left');
                    if (!isMember) return false;
                    if (this.activePlayers().length < 2) return false;
                    if (!this.state.session) return true;
                    return this.state.session.status === 'completed' || this.state.session.status === 'abandoned';
                },

                statusBannerText() {
                    if (!this.state.session) return 'Waiting for 2nd player to enter lobby...';
                    if (this.state.session.status === 'completed') {
                        if (this.state.session.is_draw) return 'Match Ended in a Tie!';
                        if (Number(this.state.session.winner_user_id) === this.currentUserId) return '🏆 VICTORY! You Won the Arena Duel!';
                        return 'Victory for @' + this.getUsernameById(this.state.session.winner_user_id);
                    }
                    return 'Arena Duel in Progress — Play & Maximize Your Score!';
                },

                statusBannerClass() {
                    if (!this.state.session) return 'text-amber-300';
                    if (this.state.session.status === 'completed') {
                        return Number(this.state.session.winner_user_id) === this.currentUserId ? 'text-emerald-400' : 'text-[#FF9900]';
                    }
                    return 'text-[#FF9900]';
                },

                async submitRunFinish() {
                    if (!this.state.session) return;
                    const finalScore = this.roomArcade?.score || 1200;
                    const level = this.roomArcade?.level || 1;
                    await this.submitMove({
                        score: finalScore,
                        level: level,
                        finished: true,
                        event: `Submitted Final Run Score (${finalScore} pts)`
                    });
                },

                async submitMove(moveData) {
                    this.busy = true;
                    this.errorMessage = null;
                    try {
                        const response = await fetch(`{{ route('rooms.moves', $room->room_code) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(moveData)
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            this.errorMessage = data.message || 'Invalid move.';
                        } else if (data.state) {
                            this.state = data.state;
                        }
                    } catch (e) {
                        this.errorMessage = 'Network error submitting score.';
                    } finally {
                        this.busy = false;
                    }
                },

                async startOrRestartMatch() {
                    this.busy = true;
                    this.errorMessage = null;
                    this.roomArcade = null;
                    try {
                        const response = await fetch(`{{ route('rooms.restart', $room->room_code) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            this.errorMessage = data.message || 'Unable to start session.';
                        } else if (data.state) {
                            this.state = data.state;
                        }
                    } catch (e) {
                        this.errorMessage = 'Failed to start match.';
                    } finally {
                        this.busy = false;
                    }
                },

                async sendInvite() {
                    if (!this.inviteUsername.trim()) return;
                    this.inviteFeedback = '';
                    try {
                        const response = await fetch(`{{ route('rooms.invite', $room->room_code) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ username: this.inviteUsername.trim() })
                        });
                        const data = await response.json();
                        if (response.ok && data.success) {
                            this.inviteFeedback = data.message;
                            this.inviteUsername = '';
                        } else {
                            this.errorMessage = data.message || 'Could not send invite.';
                        }
                    } catch (e) {}
                },

                copyRoomCode() {
                    navigator.clipboard?.writeText(this.state.room_code);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                }
            };
        }
    </script>
</x-layouts.app>
