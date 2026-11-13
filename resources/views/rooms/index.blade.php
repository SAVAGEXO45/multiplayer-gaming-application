<x-layouts.app title="Multiplayer Game Rooms">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-8"
         x-data="{ createRoomModal: false, selectedGameSlug: '{{ $gameSlug ?? ($games->first()?->slug ?? 'tic-tac-toe') }}' }">

        <!-- Page Header -->
        <div class="relative surface-card rounded-2xl p-6 sm:p-8 overflow-hidden border border-white/[0.12] flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="absolute inset-0 pointer-events-none opacity-20">
                <img src="/images/hero-arena.jpg" alt="" class="w-full h-full object-cover object-center" />
                <div class="absolute inset-0 bg-gradient-to-r from-[#0E1320] via-[#0E1320]/90 to-transparent"></div>
            </div>

            <div class="relative">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/15 border border-sky-500/30 text-xs font-bold uppercase tracking-wider text-sky-300 mb-2.5">
                    <span class="w-2 h-2 rounded-full bg-sky-400 animate-ping"></span>
                    <span>Live Matchmaking Directory</span>
                </span>
                <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-white">Multiplayer Game Rooms</h1>
                <p class="text-sm text-slate-300 mt-1">Join an open public lobby, enter a private room code, or host your own match.</p>
            </div>

            <div class="relative flex flex-wrap items-center gap-3">
                @auth
                    <button @click="joinModalOpen = true"
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#161D30] hover:bg-[#1E2740] text-slate-200 border border-white/[0.12] transition cursor-pointer">
                        Join via Room Code
                    </button>
                    <button @click="createRoomModal = true"
                            type="button"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold btn-flame cursor-pointer">
                        + Create Game Room
                    </button>
                @else
                    <a href="{{ route('login') }}"
                       class="px-5 py-2.5 rounded-xl text-xs font-bold btn-flame">
                        Sign In to Host or Join
                    </a>
                @endauth
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('rooms.index', array_filter(['status' => $statusFilter])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition {{ !$gameSlug ? 'btn-flame text-white' : 'bg-[#121726] text-slate-300 hover:text-white border border-white/[0.1]' }}">
                    All Games
                </a>
                @foreach($games as $game)
                    <a href="{{ route('rooms.index', array_filter(['game' => $game->slug, 'status' => $statusFilter])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $gameSlug === $game->slug ? 'btn-flame text-white' : 'bg-[#121726] text-slate-300 hover:text-white border border-white/[0.1]' }}">
                        {{ $game->name }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2 bg-[#101524] p-1 rounded-xl border border-white/[0.08]">
                @foreach(['' => 'All Active', 'waiting' => 'Waiting for Players', 'in_progress' => 'In Progress'] as $stKey => $stLabel)
                    <a href="{{ route('rooms.index', array_filter(['game' => $gameSlug, 'status' => $stKey ?: null])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ (string) $statusFilter === $stKey ? 'bg-purple-600 text-white' : 'text-slate-400 hover:text-white' }}">
                        {{ $stLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Rooms Grid with Game Artwork Strip -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($rooms as $room)
                @php
                    $occupied = $room->roomPlayers->whereIn('status', ['active', 'ready'])->count();
                @endphp
                <div class="surface-card surface-card-interactive rounded-2xl overflow-hidden flex flex-col justify-between group">
                    <div>
                        <div class="relative h-28 overflow-hidden border-b border-white/[0.08]">
                            <x-game-artwork :slug="$room->game->slug" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                            <div class="absolute inset-0 bg-gradient-to-t from-[#121726] via-[#121726]/40 to-transparent"></div>
                            <div class="absolute top-3 left-3 right-3 flex items-center justify-between">
                                <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-md bg-[#080B12]/85 backdrop-blur-md text-sky-300 border border-sky-500/30">
                                    #{{ $room->room_code }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase backdrop-blur-md {{ $room->status === 'waiting' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                                    {{ $room->status === 'waiting' ? 'Waiting' : 'In Progress' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5 space-y-2">
                            <span class="text-xs font-bold text-orange-400">{{ $room->game->name }}</span>
                            <h2 class="font-display text-lg font-extrabold text-white truncate">{{ $room->name }}</h2>
                            <p class="text-xs text-slate-400">
                                Host: <a href="{{ route('profile.show', $room->host->username) }}" class="text-slate-200 hover:text-orange-400 font-semibold">@{{ $room->host->username }}</a>
                            </p>
                        </div>
                    </div>

                    <div class="px-5 pb-5 pt-3 border-t border-white/[0.07] flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex -space-x-1.5">
                                @foreach($room->roomPlayers->whereIn('status', ['active', 'ready']) as $rp)
                                    <img src="{{ $rp->user->avatar_url }}" alt="{{ $rp->user->username }}" class="w-6 h-6 rounded-full ring-2 ring-[#121726] object-cover">
                                @endforeach
                            </div>
                            <span class="font-mono text-xs text-slate-300">
                                {{ $occupied }} / {{ $room->max_players }} Players
                            </span>
                        </div>

                        <a href="{{ route('rooms.show', $room->room_code) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold btn-flame">
                            Join Room
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full surface-card rounded-2xl p-12 text-center space-y-3">
                    <h3 class="font-display text-lg font-extrabold text-white">No Matching Game Rooms Found</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto">There are currently no open rooms matching your filter criteria. Host a new room to start playing immediately.</p>
                    @auth
                        <button @click="createRoomModal = true" class="mt-2 px-5 py-2.5 rounded-xl text-xs font-bold btn-flame cursor-pointer">
                            + Host New Room
                        </button>
                    @endauth
                </div>
            @endforelse
        </div>

        <!-- Create Room Modal -->
        @auth
            <div x-show="createRoomModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4">
                <div @click.outside="createRoomModal = false" class="surface-elevated rounded-2xl max-w-lg w-full p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg font-extrabold text-white">Create Multiplayer Room</h3>
                        <button @click="createRoomModal = false" class="text-slate-400 hover:text-white text-xl cursor-pointer">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('rooms.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Select Game</label>
                            <select name="game_slug" x-model="selectedGameSlug"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border border-white/[0.14] text-white text-sm focus:outline-none focus:border-orange-500">
                                @foreach($games as $game)
                                    <option value="{{ $game->slug }}">{{ $game->name }} (+{{ $game->points_per_win }} PTS / Win)</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Room Name (Optional)</label>
                            <input type="text" name="name" maxlength="80" placeholder="e.g. Tactical Championship Lobby"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border border-white/[0.14] text-white text-sm focus:outline-none focus:border-orange-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Room Visibility</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-[#080B12] border border-white/[0.14] cursor-pointer">
                                    <input type="radio" name="visibility" value="public" checked class="text-orange-500">
                                    <div>
                                        <div class="text-xs font-bold text-white">Public Lobby</div>
                                        <div class="text-[11px] text-slate-400">Open in directory</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-[#080B12] border border-white/[0.14] cursor-pointer">
                                    <input type="radio" name="visibility" value="private" class="text-purple-500">
                                    <div>
                                        <div class="text-xs font-bold text-white">Private Room</div>
                                        <div class="text-[11px] text-slate-400">Invite code only</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-2">
                            <button type="button" @click="createRoomModal = false" class="px-4 py-2 rounded-xl text-sm text-slate-300 hover:bg-white/[0.05] cursor-pointer">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold btn-flame cursor-pointer">
                                Launch Room
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</x-layouts.app>
