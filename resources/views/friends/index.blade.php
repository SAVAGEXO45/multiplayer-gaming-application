<x-layouts.app title="Friends & Squad Hub — NEXUSARENA">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-8">
        <!-- Header & Search Bar -->
        <div class="surface-card rounded-2xl p-6 sm:p-8 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold mb-3">
                    Social &amp; Squad Network
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-bold text-white tracking-tight">Friends &amp; Squad Hub</h1>
                <p class="text-sm text-slate-400 mt-1">Search for competitors, manage friend requests, monitor online presence, and dispatch direct room invites.</p>
            </div>

            <form method="GET" action="{{ route('friends.index') }}" class="flex gap-2.5 w-full lg:w-96">
                <input type="text" name="q" value="{{ $search }}" placeholder="Search players by username or name..."
                       class="flex-1 px-3.5 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
                <button type="submit" class="px-4 py-2.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition cursor-pointer shrink-0">
                    Search
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left 7 Columns: Friends List & Player Discovery -->
            <div class="lg:col-span-7 space-y-6">
                <!-- My Friends List -->
                <div class="surface-card rounded-xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="font-display text-lg font-bold text-white">My Friends ({{ $friends->count() }})</h2>
                        <span class="inline-flex items-center gap-1.5 text-xs text-emerald-400 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            {{ $friends->where('is_online', true)->count() }} Online
                        </span>
                    </div>

                    <div class="space-y-3">
                        @forelse($friends as $friend)
                            <div class="p-4 rounded-xl bg-[#0D111A] border border-[#1E2738] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <a href="{{ route('profile.show', $friend->username) }}" class="flex items-center gap-3.5 group">
                                    <div class="relative shrink-0">
                                        <img src="{{ $friend->avatar_url }}" alt="" class="w-11 h-11 rounded-lg object-cover border border-[#263147]">
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full {{ $friend->is_online ? 'bg-emerald-400' : 'bg-slate-600' }} ring-2 ring-[#0D111A]"></span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-white group-hover:text-blue-400 transition text-sm flex items-center gap-2">
                                            {{ $friend->name }}
                                            <span class="text-[10px] px-2 py-0.5 rounded font-semibold {{ $friend->is_online ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-[#161C2B] text-slate-400' }}">
                                                {{ $friend->is_online ? 'Online' : 'Offline' }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-0.5">
                                            @{{ $friend->username }} &bull; <span class="font-mono text-white">{{ number_format($friend->globalStatistic?->total_points ?? 0) }}</span> PTS &bull; {{ $friend->globalStatistic?->games_won ?? 0 }} Wins
                                        </div>
                                    </div>
                                </a>

                                <div class="flex items-center gap-2">
                                    @if($myOpenRooms->isNotEmpty())
                                        <form method="POST" action="{{ route('rooms.invite', $myOpenRooms->first()->room_code) }}">
                                            @csrf
                                            <input type="hidden" name="username" value="{{ $friend->username }}">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition cursor-pointer">
                                                Invite to #{{ $myOpenRooms->first()->room_code }}
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('friends.remove', $friend->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/25 transition cursor-pointer">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-xs text-slate-400 bg-[#0D111A] rounded-xl border border-[#1E2738]">
                                You haven't added any friends yet. Use the player directory below to send friend requests.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Player Search / Suggested Challengers -->
                <div class="surface-card rounded-xl p-6">
                    <h2 class="font-display text-lg font-bold text-white mb-4">
                        {{ $search !== '' ? 'Search Results for "' . $search . '"' : 'Discover Players' }}
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse($searchResults as $candidate)
                            <div class="p-3.5 rounded-xl bg-[#0D111A] border border-[#1E2738] flex items-center justify-between gap-3">
                                <a href="{{ route('profile.show', $candidate->username) }}" class="flex items-center gap-3 min-w-0 group">
                                    <img src="{{ $candidate->avatar_url }}" alt="" class="w-9 h-9 rounded-lg object-cover border border-[#263147] shrink-0">
                                    <div class="truncate">
                                        <div class="text-xs font-semibold text-white group-hover:text-blue-400 transition truncate">{{ $candidate->name }}</div>
                                        <div class="text-[11px] text-slate-400 truncate">@{{ $candidate->username }} &bull; {{ number_format($candidate->globalStatistic?->total_points ?? 0) }} PTS</div>
                                    </div>
                                </a>

                                @if(in_array($candidate->id, $friendIds, true))
                                    <span class="px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px] font-semibold">Friends</span>
                                @elseif(in_array($candidate->id, $pendingUserIds, true))
                                    <span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[11px] font-semibold">Pending</span>
                                @else
                                    <form method="POST" action="{{ route('friends.request') }}">
                                        @csrf
                                        <input type="hidden" name="username" value="{{ $candidate->username }}">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#161C2B] hover:bg-blue-600 text-white border border-[#263147] hover:border-blue-500 transition cursor-pointer">
                                            + Add
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="col-span-full text-center py-6 text-xs text-slate-400">No matching players found.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right 5 Columns: Friend Requests & Recent Friend Activity -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Incoming Friend Requests -->
                <div class="surface-card rounded-xl p-6">
                    <h2 class="font-display text-lg font-bold text-white mb-4">
                        Incoming Requests ({{ $incomingRequests->count() }})
                    </h2>
                    <div class="space-y-3">
                        @forelse($incomingRequests as $req)
                            <div class="p-3.5 rounded-xl bg-[#0D111A] border border-blue-500/30 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $req->sender->avatar_url }}" alt="" class="w-9 h-9 rounded-lg object-cover border border-[#263147]">
                                    <div>
                                        <div class="text-xs font-semibold text-white">{{ $req->sender->name }}</div>
                                        <div class="text-[11px] text-slate-400">@{{ $req->sender->username }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('friends.accept', $req->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition cursor-pointer">
                                            Accept
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('friends.reject', $req->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-[#161C2B] hover:bg-rose-500/20 text-slate-300 hover:text-rose-300 border border-[#263147] transition cursor-pointer">
                                            Decline
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-xs text-slate-400 bg-[#0D111A] rounded-xl border border-[#1E2738]">No pending incoming friend requests.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Friend Activity Feed -->
                <div class="surface-card rounded-xl p-6">
                    <h2 class="font-display text-lg font-bold text-white mb-4">Recent Squad Activity</h2>
                    <div class="space-y-3">
                        @forelse($recentFriendActivity as $act)
                            <div class="p-3.5 rounded-xl bg-[#0D111A] border border-[#1E2738] text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-blue-400">{{ $act->game->name }}</span>
                                    <span class="text-slate-500">{{ $act->ended_at?->diffForHumans() }}</span>
                                </div>
                                <div class="text-slate-300">
                                    @if($act->is_draw)
                                        Match in room <span class="font-mono text-white">#{{ $act->room->room_code }}</span> ended in a draw.
                                    @elseif($act->winner)
                                        <strong class="text-white">@{{ $act->winner->username }}</strong> won in room <span class="font-mono text-white">#{{ $act->room->room_code }}</span>!
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-xs text-slate-400 bg-[#0D111A] rounded-xl border border-[#1E2738]">Friend match activity will appear here.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
