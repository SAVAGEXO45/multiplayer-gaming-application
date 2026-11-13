<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — NEXUSARENA' : 'NEXUSARENA — Play Action Games, Compete & Win' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Montserrat:ital,wght@0,700;0,800;0,900;1,800;1,900&family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen arena-bg text-[#F8FAFC] flex flex-col selection:bg-[#FF9900] selection:text-slate-950"
      x-data="{ mobileMenuOpen: false, joinModalOpen: false, liveToast: null }"
      x-init="
        @auth
        if (window.Echo) {
            try {
                window.Echo.private('App.Models.User.{{ auth()->id() }}')
                    .listen('.notification.received', (e) => {
                        liveToast = e;
                        setTimeout(() => { if (liveToast && liveToast.id === e.id) liveToast = null; }, 6000);
                    });
            } catch (err) {}
        }
        @endauth
      ">

    <!-- Top Accent Bar -->
    <div class="h-1 w-full bg-gradient-to-r from-[#FF9900] via-[#EF4444] to-[#00D2FF]"></div>

    <!-- Main Navigation Header -->
    <header class="sticky top-0 z-40 border-b border-white/[0.09] bg-[#070A12]/95 backdrop-blur-xl">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-[72px] gap-4">
                <!-- Gaming Brand Logo + Navigation -->
                <div class="flex items-center gap-6 min-w-0">
                    <a href="{{ route('landing') }}" class="flex items-center gap-3 shrink-0 group focus:outline-none">
                        <div class="w-11 h-11 rounded-xl overflow-hidden border border-orange-500/40 bg-[#0B0E1A] flex items-center justify-center group-hover:scale-105 transition">
                            <img src="/images/ref/mascot-logo.jpg" alt="NexusArena Logo" class="w-full h-full object-cover" />
                        </div>
                        <div class="leading-none">
                            <span class="font-display font-black italic text-lg tracking-tight text-white block">
                                NEXUS<span class="text-[#FF9900]">ARENA</span>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mt-0.5">
                                ACTION GAMING PLATFORM
                            </span>
                        </div>
                    </a>

                    <!-- Primary Navigation Links -->
                    <nav class="hidden xl:flex items-center gap-1 bg-[#0D121F] p-1.5 rounded-xl border border-white/[0.08]">
                        <a href="{{ route('landing') }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ request()->routeIs('landing') ? 'bg-[#FF9900] text-slate-950' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]' }}">
                            Home
                        </a>
                        <a href="{{ route('games.index') }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ request()->routeIs('games.*') ? 'bg-[#FF9900] text-slate-950' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]' }}">
                            Games
                        </a>
                        <a href="{{ route('landing') }}#categories"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide text-slate-300 hover:text-white hover:bg-white/[0.05] transition">
                            Categories
                        </a>
                        <a href="{{ route('leaderboards.index') }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ request()->routeIs('leaderboards.*') ? 'bg-[#FF9900] text-slate-950' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]' }}">
                            Leaderboards
                        </a>
                        <a href="{{ route('rooms.index') }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide transition flex items-center gap-1.5 {{ request()->routeIs('rooms.*') ? 'bg-[#FF9900] text-slate-950' : 'text-slate-300 hover:text-white hover:bg-white/[0.05]' }}">
                            <span>Multiplayer</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        </a>
                        @auth
                            <a href="{{ route('library.index') }}"
                               class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ request()->routeIs('library.*') ? 'bg-[#FF9900] text-slate-950' : 'text-amber-300 hover:text-white hover:bg-white/[0.05]' }}">
                                My Library
                            </a>
                            <a href="{{ route('dashboard') }}"
                               class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ request()->routeIs('dashboard') ? 'bg-sky-500 text-slate-950' : 'text-sky-300 hover:text-white hover:bg-white/[0.05]' }}">
                                Dashboard
                            </a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}"
                                   class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide text-orange-400 hover:bg-orange-500/15 transition">
                                    Admin
                                </a>
                            @endif
                        @endauth
                    </nav>
                </div>

                <!-- Instant Game Search + Right Controls -->
                <div class="hidden md:flex items-center gap-3 shrink-0">
                    <form action="{{ route('games.index') }}" method="GET" class="relative w-52 lg:w-64">
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Search FPS, Racing, RPG..."
                               class="w-full pl-9 pr-3 py-2 rounded-xl bg-[#0D121F] border border-white/[0.1] text-xs text-white placeholder-slate-400 focus:outline-none focus:border-[#FF9900] transition">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </form>

                    @auth
                        <button @click="joinModalOpen = true"
                                type="button"
                                class="px-3 py-2 rounded-xl text-xs font-bold bg-[#0E1322] hover:bg-[#171F36] text-sky-300 border border-sky-500/30 flex items-center gap-1.5 transition cursor-pointer">
                            <span># Room Code</span>
                        </button>

                        @php
                            $unreadCount = auth()->user()->unreadPlatformNotifications()->count();
                        @endphp
                        <a href="{{ route('notifications.index') }}"
                           class="relative p-2.5 rounded-xl bg-[#0E1322] border border-white/[0.1] hover:border-orange-500/40 text-slate-300 hover:text-white transition"
                           title="Notifications">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute -top-1 -right-1 px-1 min-w-[17px] h-[17px] rounded-full bg-[#FF9900] text-[10px] font-black text-slate-950 flex items-center justify-center">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('friends.index') }}"
                           class="p-2.5 rounded-xl bg-[#0E1322] border border-white/[0.1] hover:border-sky-500/40 text-slate-300 hover:text-white transition"
                           title="Friends Hub">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </a>

                        <a href="{{ route('profile.show', auth()->user()->username) }}"
                           class="flex items-center gap-2.5 pl-1.5 pr-3 py-1 rounded-xl bg-[#0E1322] border border-white/[0.14] hover:border-[#FF9900] transition">
                            <div class="relative">
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}" class="w-7 h-7 rounded-lg object-cover">
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-[#05070E]"></span>
                            </div>
                            <div class="text-left">
                                <div class="text-xs font-bold text-white leading-tight truncate max-w-[100px]">{{ auth()->user()->username }}</div>
                                <div class="text-[10px] text-[#FF9900] uppercase font-bold">{{ auth()->user()->role }}</div>
                            </div>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="p-2.5 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
                                    title="Log Out">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#0E1322] hover:bg-[#171F36] border border-white/[0.14] transition">
                            Login
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-5 py-2 rounded-xl text-xs font-extrabold btn-start-playing">
                            Create Account
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex xl:hidden items-center gap-2">
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            type="button"
                            class="p-2 rounded-xl bg-[#0E1322] border border-white/[0.12] text-slate-200 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Responsive Drawer -->
        <div x-show="mobileMenuOpen" x-cloak class="xl:hidden border-t border-white/[0.08] bg-[#080B14] px-4 pt-3 pb-5 space-y-2">
            <form action="{{ route('games.index') }}" method="GET" class="mb-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search games..."
                       class="w-full px-3.5 py-2.5 rounded-xl bg-[#0D121F] border border-white/[0.12] text-sm text-white">
            </form>
            <a href="{{ route('landing') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-white hover:bg-white/[0.05]">Home</a>
            <a href="{{ route('games.index') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-slate-200 hover:bg-white/[0.05]">All Action Games</a>
            <a href="{{ route('landing') }}#categories" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-sm font-bold text-slate-200 hover:bg-white/[0.05]">Game Categories</a>
            <a href="{{ route('leaderboards.index') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-slate-200 hover:bg-white/[0.05]">Leaderboards</a>
            <a href="{{ route('rooms.index') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-slate-200 hover:bg-white/[0.05]">Multiplayer Rooms</a>
            <a href="{{ route('tournaments.index') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-slate-200 hover:bg-white/[0.05]">Tournaments</a>
            @auth
                <div class="border-t border-white/[0.08] my-2 pt-2 space-y-1">
                    <a href="{{ route('library.index') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-amber-400 hover:bg-white/[0.05]">My Game Library &amp; Favorites</a>
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-sky-400 hover:bg-white/[0.05]">Player Dashboard</a>
                    <a href="{{ route('friends.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-200 hover:bg-white/[0.05]">Friends</a>
                    <a href="{{ route('profile.show', auth()->user()->username) }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-200 hover:bg-white/[0.05]">My Profile</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg text-sm font-bold text-[#FF9900]">Admin Command Center</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="pt-1">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm font-bold text-rose-400">Log Out</button>
                    </form>
                </div>
            @else
                <div class="pt-3 grid grid-cols-2 gap-2.5 border-t border-white/[0.08]">
                    <a href="{{ route('login') }}" class="text-center py-2.5 rounded-xl border border-white/[0.15] bg-[#0E1322] text-sm font-bold text-white">Login</a>
                    <a href="{{ route('register') }}" class="text-center py-2.5 rounded-xl btn-start-playing text-sm font-extrabold">Create Account</a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Real-Time WebSocket Toast Alert -->
    <div x-show="liveToast" x-cloak
         x-transition
         class="fixed bottom-5 right-5 z-50 max-w-sm w-full surface-card rounded-2xl p-4 border-l-4 border-l-[#FF9900] shadow-2xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[#FF9900]" x-text="liveToast?.title"></p>
                <p class="text-sm text-slate-200 mt-1" x-text="liveToast?.message"></p>
                <template x-if="liveToast?.action_url">
                    <a :href="liveToast.action_url" class="inline-block mt-2 text-xs font-semibold text-sky-400 hover:text-sky-300">
                        View Details &rarr;
                    </a>
                </template>
            </div>
            <button @click="liveToast = null" class="text-slate-400 hover:text-white">&times;</button>
        </div>
    </div>

    <!-- Flash Feedback Notifications -->
    @if(session('success') || $errors->any())
        <div class="max-w-[1400px] mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
            @if(session('success'))
                <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-sm text-emerald-300 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 px-4 py-3 text-sm text-rose-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- Main Page Content -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    <!-- Join Room by Code Modal -->
    @auth
        <div x-show="joinModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div @click.outside="joinModalOpen = false" class="surface-elevated rounded-2xl max-w-md w-full p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#FF9900]"></span>
                        <h3 class="font-display text-lg font-bold text-white">Join Room by Invite Code</h3>
                    </div>
                    <button @click="joinModalOpen = false" class="text-slate-400 hover:text-white text-lg cursor-pointer">&times;</button>
                </div>
                <p class="text-sm text-slate-400 mb-4">Enter the 6-character room code shared by the host to jump directly into the match lobby.</p>
                <form method="POST" action="{{ route('rooms.join-code') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Room Code</label>
                        <input type="text" name="room_code" required maxlength="12" placeholder="e.g. NX79K2"
                               class="w-full px-4 py-3 rounded-xl bg-[#05070E] border border-white/[0.15] text-white font-mono tracking-widest uppercase text-center text-base focus:outline-none focus:border-[#FF9900]">
                    </div>
                    <div class="flex justify-end gap-2.5">
                        <button type="button" @click="joinModalOpen = false" class="px-4 py-2 rounded-xl text-sm text-slate-300 hover:bg-white/[0.05] cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-extrabold btn-start-playing cursor-pointer">
                            Connect to Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endauth

    <!-- Footer -->
    <footer class="border-t border-white/[0.08] bg-[#04060C] mt-20">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 border-b border-white/[0.08]">
                <div class="md:col-span-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="/images/ref/mascot-logo.jpg" alt="NexusArena Mascot" class="w-10 h-10 rounded-xl object-cover border border-orange-500/40" />
                        <div>
                            <span class="font-display font-black italic text-lg text-white block leading-none">
                                NEXUS<span class="text-[#FF9900]">ARENA</span>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Play. Compete. Win.</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
                        Instant-play 60FPS action games with Web Audio SFX, personal game libraries, multiplayer lobbies, and real-time global leaderboards.
                    </p>
                </div>

                <div class="md:col-span-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-3.5">Top Action Titles</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li><a href="{{ route('games.show', 'cyber-strike-fps') }}" class="hover:text-[#FF9900] transition">Cyber Strike: Tactical Ops (FPS)</a></li>
                        <li><a href="{{ route('games.show', 'nitro-apex-gt') }}" class="hover:text-[#FF9900] transition">Nitro Apex GT: Street Horizon</a></li>
                        <li><a href="{{ route('games.show', 'shadow-dojo-brawl') }}" class="hover:text-[#FF9900] transition">Shadow Dojo: Iron Fist</a></li>
                        <li><a href="{{ route('games.show', 'deadzone-outbreak') }}" class="hover:text-[#FF9900] transition">Deadzone: Outbreak 2099</a></li>
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-3.5">Explore &amp; Compete</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li><a href="{{ route('games.index') }}" class="hover:text-sky-400 transition">Browse All 10 Action Games</a></li>
                        <li><a href="{{ route('rooms.index') }}" class="hover:text-sky-400 transition">Multiplayer Arena Lobbies</a></li>
                        <li><a href="{{ route('leaderboards.index') }}" class="hover:text-sky-400 transition">Global High-Score Leaderboards</a></li>
                        <li><a href="{{ route('tournaments.index') }}" class="hover:text-sky-400 transition">Championship Tournaments</a></li>
                    </ul>
                </div>

                <div class="md:col-span-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-3.5">Player Hub</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        @auth
                            <li><a href="{{ route('library.index') }}" class="hover:text-[#FF9900] transition">My Game Library</a></li>
                            <li><a href="{{ route('dashboard') }}" class="hover:text-sky-400 transition">Player Dashboard</a></li>
                            <li><a href="{{ route('profile.show', auth()->user()->username) }}" class="hover:text-sky-400 transition">My Profile</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="hover:text-sky-400 transition">Login</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-[#FF9900] transition">Create Account</a></li>
                        @endauth
                    </ul>
                </div>
            </div>

            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <span>&copy; {{ date('Y') }} NEXUSARENA. All 10 Action Games Playable Instantly in Browser.</span>
                <div class="flex items-center gap-4">
                    <span class="text-slate-400">60FPS HTML5 Canvas Engine</span>
                    <span>&bull;</span>
                    <span class="text-slate-400">Live High-Score Leaderboards</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
