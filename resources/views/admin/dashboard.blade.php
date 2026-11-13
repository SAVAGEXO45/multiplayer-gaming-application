<x-layouts.app title="Admin Command Center — NEXUSARENA">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-8" x-data="{ activeTab: 'users' }">
        <!-- Header -->
        <div class="surface-card rounded-2xl p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-purple-500/10 border border-purple-500/25 text-purple-300 text-xs font-semibold mb-3">
                    Administrator Control Panel
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-bold text-white tracking-tight">Platform Command Center</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Monitor live multiplayer rooms, manage player accounts, configure games, and moderate reports.</p>
            </div>

            <div class="flex flex-wrap gap-1.5 bg-[#0D111A] p-1.5 rounded-xl border border-[#1E2738]">
                @foreach(['users' => 'Users & Accounts', 'games' => 'Games Catalog', 'rooms' => 'Active Rooms', 'achievements' => 'Achievements', 'reports' => 'Reports (' . $metrics['open_reports'] . ')'] as $tabKey => $tabLabel)
                    <button type="button"
                            @click="activeTab = '{{ $tabKey }}'"
                            :class="activeTab === '{{ $tabKey }}' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                            class="px-3.5 py-2 rounded-lg text-xs font-semibold transition cursor-pointer">
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Platform Telemetry KPI Row -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="surface-card rounded-xl p-4">
                <div class="text-[11px] font-medium text-slate-400">Registered Users</div>
                <div class="font-display text-2xl font-bold text-white mt-1">{{ $metrics['total_users'] }}</div>
            </div>
            <div class="surface-card rounded-xl p-4">
                <div class="text-[11px] font-medium text-slate-400">Online Players</div>
                <div class="font-display text-2xl font-bold text-emerald-400 mt-1">{{ $metrics['online_users'] }}</div>
            </div>
            <div class="surface-card rounded-xl p-4">
                <div class="text-[11px] font-medium text-slate-400">Suspended</div>
                <div class="font-display text-2xl font-bold text-rose-400 mt-1">{{ $metrics['suspended_users'] }}</div>
            </div>
            <div class="surface-card rounded-xl p-4">
                <div class="text-[11px] font-medium text-slate-400">Active Games</div>
                <div class="font-display text-2xl font-bold text-blue-400 mt-1">{{ $metrics['active_games'] }} / {{ $metrics['total_games'] }}</div>
            </div>
            <div class="surface-card rounded-xl p-4">
                <div class="text-[11px] font-medium text-slate-400">Live Rooms</div>
                <div class="font-display text-2xl font-bold text-purple-400 mt-1">{{ $metrics['active_rooms'] }}</div>
            </div>
            <div class="surface-card rounded-xl p-4">
                <div class="text-[11px] font-medium text-slate-400">Matches Completed</div>
                <div class="font-display text-2xl font-bold text-amber-400 mt-1">{{ $metrics['completed_sessions'] }}</div>
            </div>
        </div>

        <!-- TAB 1: USER MANAGEMENT -->
        <div x-show="activeTab === 'users'" class="surface-card rounded-xl p-6">
            <h2 class="font-display text-lg font-bold text-white mb-4">Registered Players &amp; Account Moderation</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-[#1E2738] text-[11px] font-semibold uppercase tracking-wider text-slate-400 bg-[#0D111A]">
                            <th class="py-3 px-4">Player</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Role</th>
                            <th class="py-3 px-4">Points / Wins</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1E2738]">
                        @foreach($users as $u)
                            <tr class="hover:bg-[#161C2B]/60 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-white">{{ $u->name }}</div>
                                    <div class="text-xs text-slate-400">@{{ $u->username }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-300">{{ $u->email }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-semibold uppercase {{ $u->isAdmin() ? 'bg-purple-500/15 text-purple-300 border border-purple-500/25' : 'bg-[#0D111A] text-slate-300 border border-[#263147]' }}">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs">
                                    <strong class="font-mono text-white">{{ number_format($u->globalStatistic?->total_points ?? 0) }} PTS</strong>
                                    <span class="text-slate-400">({{ $u->globalStatistic?->games_won ?? 0 }}W)</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($u->is_suspended)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rose-500/15 text-rose-300 border border-rose-500/25 uppercase">Suspended</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 uppercase">Active</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.toggle-suspension', $u->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer transition {{ $u->is_suspended ? 'bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25' : 'bg-rose-500/15 text-rose-300 hover:bg-rose-500/25' }}">
                                                {{ $u->is_suspended ? 'Reactivate' : 'Suspend' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: GAMES MANAGEMENT -->
        <div x-show="activeTab === 'games'" x-cloak class="surface-card rounded-xl p-6">
            <h2 class="font-display text-lg font-bold text-white mb-4">Manage Platform Games &amp; Scoring</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($games as $g)
                    <div class="p-5 rounded-xl bg-[#0D111A] border border-[#1E2738] space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-display text-base font-bold text-white">{{ $g->name }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $g->sessions_count }} Total Sessions &bull; {{ $g->active_rooms_count }} Active Rooms</p>
                            </div>
                            <form method="POST" action="{{ route('admin.games.toggle', $g->slug) }}">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer {{ $g->is_active ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-300 border border-rose-500/30' }}">
                                    {{ $g->is_active ? 'Enabled (Disable)' : 'Disabled (Enable)' }}
                                </button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('admin.games.points', $g->slug) }}" class="grid grid-cols-3 gap-3 items-end pt-3 border-t border-[#1E2738]">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="block text-[10px] uppercase text-slate-400 mb-1">Win Points</label>
                                <input type="number" name="points_per_win" value="{{ $g->points_per_win }}"
                                       class="w-full px-3 py-1.5 rounded-lg bg-[#111622] border border-[#263147] text-xs text-white">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase text-slate-400 mb-1">Draw Points</label>
                                <input type="number" name="points_per_draw" value="{{ $g->points_per_draw }}"
                                       class="w-full px-3 py-1.5 rounded-lg bg-[#111622] border border-[#263147] text-xs text-white">
                            </div>
                            <button type="submit" class="py-1.5 px-3 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-semibold text-white transition cursor-pointer">
                                Save Scoring
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- TAB 3: ACTIVE ROOMS & SESSIONS -->
        <div x-show="activeTab === 'rooms'" x-cloak class="space-y-6">
            <div class="surface-card rounded-xl p-6">
                <h2 class="font-display text-lg font-bold text-white mb-4">Active Multiplayer Rooms</h2>
                <div class="space-y-3">
                    @forelse($activeRooms as $r)
                        <div class="p-4 rounded-xl bg-[#0D111A] border border-[#1E2738] flex items-center justify-between">
                            <div>
                                <span class="font-mono text-xs font-bold text-blue-400">#{{ $r->room_code }}</span>
                                <span class="text-sm font-semibold text-white ml-2">{{ $r->name }}</span>
                                <span class="text-xs text-slate-400 ml-2">({{ $r->game->name }} &bull; Host: @{{ $r->host->username }})</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('rooms.show', $r->room_code) }}" class="text-xs text-blue-400 hover:underline">Inspect</a>
                                <form method="POST" action="{{ route('admin.rooms.close', $r->room_code) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded-lg bg-rose-500/15 text-rose-300 border border-rose-500/25 text-xs font-semibold cursor-pointer">
                                        Force Close
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-400">No active rooms currently open.</div>
                    @endforelse
                </div>
            </div>

            <div class="surface-card rounded-xl p-6">
                <h2 class="font-display text-lg font-bold text-white mb-4">Recent Game Sessions</h2>
                <div class="space-y-2">
                    @foreach($recentSessions as $s)
                        <div class="p-3 rounded-lg bg-[#0D111A] border border-[#1E2738] text-xs flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-white">Session #{{ $s->id }}</span> &bull;
                                <span class="text-blue-400">{{ $s->game->name }}</span> (Room #{{ $s->room->room_code }})
                            </div>
                            <div>
                                <span class="uppercase font-semibold {{ $s->status === 'completed' ? 'text-emerald-400' : 'text-amber-400' }}">{{ $s->status }}</span>
                                <span class="text-slate-500 ml-2">{{ $s->moves_count }} moves</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- TAB 4: ACHIEVEMENTS -->
        <div x-show="activeTab === 'achievements'" x-cloak class="surface-card rounded-xl p-6 space-y-6">
            <h2 class="font-display text-lg font-bold text-white">Platform Achievements ({{ $achievements->count() }})</h2>

            <form method="POST" action="{{ route('admin.achievements.store') }}" class="p-4 rounded-xl bg-[#0D111A] border border-[#1E2738] grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-[10px] uppercase text-slate-400 mb-1">Badge Name</label>
                    <input type="text" name="name" required placeholder="Grandmaster" class="w-full px-3 py-2 rounded-lg bg-[#111622] border border-[#263147] text-xs text-white">
                </div>
                <div>
                    <label class="block text-[10px] uppercase text-slate-400 mb-1">Description</label>
                    <input type="text" name="description" required placeholder="Win 25 matches" class="w-full px-3 py-2 rounded-lg bg-[#111622] border border-[#263147] text-xs text-white">
                </div>
                <div>
                    <label class="block text-[10px] uppercase text-slate-400 mb-1">Criteria Type</label>
                    <select name="criteria_type" class="w-full px-3 py-2 rounded-lg bg-[#111622] border border-[#263147] text-xs text-white">
                        <option value="games_played">Games Played</option>
                        <option value="games_won">Games Won</option>
                        <option value="total_points">Total Points</option>
                        <option value="win_streak">Win Streak</option>
                        <option value="friends_count">Friends Count</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] uppercase text-slate-400 mb-1">Target</label>
                        <input type="number" name="criteria_value" value="10" required class="w-full px-3 py-2 rounded-lg bg-[#111622] border border-[#263147] text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase text-slate-400 mb-1">Reward PTS</label>
                        <input type="number" name="points_reward" value="150" required class="w-full px-3 py-2 rounded-lg bg-[#111622] border border-[#263147] text-xs text-white">
                    </div>
                </div>
                <button type="submit" class="py-2 px-4 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-semibold text-white transition cursor-pointer">
                    + Add Achievement
                </button>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($achievements as $a)
                    <div class="p-4 rounded-xl bg-[#0D111A] border border-[#1E2738] flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-white">{{ $a->name }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $a->description }}</div>
                        </div>
                        <span class="text-xs font-mono font-semibold text-amber-400">+{{ $a->points_reward }} PTS</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- TAB 5: REPORTS MODERATION -->
        <div x-show="activeTab === 'reports'" x-cloak class="surface-card rounded-xl p-6">
            <h2 class="font-display text-lg font-bold text-white mb-4">Player Reports &amp; Moderation Queue</h2>
            <div class="space-y-3">
                @forelse($reports as $rep)
                    <div class="p-4 rounded-xl bg-[#0D111A] border border-[#1E2738] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $rep->status === 'open' ? 'bg-amber-500/15 text-amber-300' : 'bg-emerald-500/15 text-emerald-400' }}">
                                    {{ $rep->status }}
                                </span>
                                <span class="text-sm font-semibold text-white">{{ $rep->reason }}</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                Reported by @{{ $rep->reporter?->username }}
                                @if($rep->reportedUser) against <strong>@{{ $rep->reportedUser->username }}</strong> @endif
                                &bull; {{ $rep->details }}
                            </p>
                        </div>

                        @if($rep->status === 'open')
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.reports.resolve', $rep->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold cursor-pointer">
                                        Resolve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.reports.resolve', $rep->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="dismissed">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#161C2B] text-slate-300 border border-[#263147] text-xs font-medium cursor-pointer">
                                        Dismiss
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-8 text-xs text-slate-400">No player reports in the queue.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
