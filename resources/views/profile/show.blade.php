<x-layouts.app :title="$profileUser->username . ' — Competitor Profile'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-8" x-data="{ editModal: false }">
        <!-- Profile Banner -->
        <div class="surface-card rounded-2xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <div class="relative shrink-0">
                    <img src="{{ $profileUser->avatar_url }}" alt="{{ $profileUser->username }}" class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border border-[#263147] shadow-lg">
                    <span class="absolute -bottom-1 -right-1 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase {{ $profileUser->is_online ? 'bg-emerald-500 text-slate-950' : 'bg-[#161C2B] text-slate-400 border border-[#263147]' }}">
                        {{ $profileUser->is_online ? 'Online' : 'Offline' }}
                    </span>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold uppercase bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            {{ $profileUser->role }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono font-semibold uppercase bg-amber-500/10 text-amber-300 border border-amber-500/20">
                            {{ number_format($globalStat->total_points) }} PTS
                        </span>
                    </div>
                    <h1 class="font-display text-2xl sm:text-3xl font-bold text-white mt-1.5">{{ $profileUser->name }}</h1>
                    <p class="text-sm text-slate-400 font-medium">@{{ $profileUser->username }}</p>
                    <p class="text-xs text-slate-300 mt-2 max-w-xl leading-relaxed">{{ $profileUser->bio ?: 'Competitive challenger on NEXUSARENA.' }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($isOwnProfile)
                    <button @click="editModal = true"
                            class="px-4 py-2.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-sm transition cursor-pointer">
                        Edit Profile &amp; Avatar
                    </button>
                @elseif(auth()->check() && !$isFriend)
                    <form method="POST" action="{{ route('friends.request') }}">
                        @csrf
                        <input type="hidden" name="username" value="{{ $profileUser->username }}">
                        <button type="submit" class="px-4 py-2.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition cursor-pointer">
                            + Add Friend
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Career Statistics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="surface-card rounded-xl p-5">
                <div class="text-xs text-slate-400 font-medium">Total Matches</div>
                <div class="font-display text-2xl font-bold text-white mt-1.5">{{ $globalStat->games_played }}</div>
            </div>
            <div class="surface-card rounded-xl p-5">
                <div class="text-xs text-slate-400 font-medium">Total Wins</div>
                <div class="font-display text-2xl font-bold text-emerald-400 mt-1.5">{{ $globalStat->games_won }}</div>
            </div>
            <div class="surface-card rounded-xl p-5">
                <div class="text-xs text-slate-400 font-medium">Win Percentage</div>
                <div class="font-display text-2xl font-bold text-blue-400 mt-1.5">{{ number_format($globalStat->win_percentage, 1) }}%</div>
            </div>
            <div class="surface-card rounded-xl p-5">
                <div class="text-xs text-slate-400 font-medium">Best Win Streak</div>
                <div class="font-display text-2xl font-bold text-amber-400 mt-1.5">{{ $globalStat->best_streak }} W</div>
            </div>
        </div>

        <!-- Achievements Showcase -->
        <div class="surface-card rounded-xl p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Career Honours</span>
                    <h2 class="font-display text-xl font-bold text-white mt-0.5">Achievements ({{ count($unlockedMap) }} / {{ $allAchievements->count() }})</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($allAchievements as $ach)
                    @php
                        $isUnlocked = isset($unlockedMap[$ach->id]);
                    @endphp
                    <div class="p-4 rounded-xl border transition flex items-start gap-3.5 {{ $isUnlocked ? 'bg-[#0D111A] border-amber-500/30' : 'bg-[#0D111A]/60 border-[#1E2738] opacity-55' }}">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 {{ $isUnlocked ? 'bg-amber-500/15 text-amber-300 border border-amber-500/30' : 'bg-[#161C2B] text-slate-500' }}">
                            {{ $isUnlocked ? '★' : '🔒' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-white truncate">{{ $ach->name }}</h3>
                                <span class="text-xs font-mono font-semibold text-amber-400 shrink-0">+{{ $ach->points_reward }} PTS</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">{{ $ach->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Edit Profile & Password Modal -->
        @if($isOwnProfile)
            <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4 overflow-y-auto">
                <div @click.outside="editModal = false" class="surface-elevated rounded-2xl max-w-xl w-full p-6 sm:p-8 space-y-6 my-8 shadow-2xl">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-xl font-bold text-white">Update Competitor Profile</h3>
                        <button @click="editModal = false" class="text-slate-400 hover:text-white text-xl cursor-pointer">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Display Name</label>
                                <input type="text" name="name" value="{{ old('name', $profileUser->name) }}" required
                                       class="w-full px-3.5 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-sm text-white focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username</label>
                                <input type="text" name="username" value="{{ old('username', $profileUser->username) }}" required
                                       class="w-full px-3.5 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-sm text-white focus:outline-none focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Competitor Bio</label>
                            <textarea name="bio" rows="3" maxlength="500"
                                      class="w-full px-3.5 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-sm text-white focus:outline-none focus:border-blue-500">{{ old('bio', $profileUser->bio) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Profile Picture (Avatar Upload)</label>
                            <input type="file" name="avatar" accept="image/*"
                                   class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500">
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" @click="editModal = false" class="px-4 py-2 rounded-lg text-xs text-slate-300 hover:text-white cursor-pointer">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition cursor-pointer">
                                Save Changes
                            </button>
                        </div>
                    </form>

                    <!-- Change Password Form -->
                    <form method="POST" action="{{ route('profile.password') }}" class="pt-5 border-t border-[#1E2738] space-y-3">
                        @csrf
                        @method('PUT')
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-blue-400">Update Password</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <input type="password" name="current_password" placeholder="Current password" required
                                   class="px-3 py-2 rounded-lg bg-[#0D111A] border border-[#263147] text-xs text-white focus:outline-none focus:border-blue-500">
                            <input type="password" name="password" placeholder="New password" required
                                   class="px-3 py-2 rounded-lg bg-[#0D111A] border border-[#263147] text-xs text-white focus:outline-none focus:border-blue-500">
                            <input type="password" name="password_confirmation" placeholder="Confirm new" required
                                   class="px-3 py-2 rounded-lg bg-[#0D111A] border border-[#263147] text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="text-right">
                            <button type="submit" class="px-4 py-2 rounded-lg text-xs font-semibold bg-[#161C2B] hover:bg-blue-600 text-white border border-[#263147] transition cursor-pointer">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
