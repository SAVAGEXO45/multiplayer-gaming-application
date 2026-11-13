<x-layouts.app title="Create Account">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
         x-data="{
            showPassword: false,
            showConfirm: false,
            submitting: false
         }">
        <div class="surface-card rounded-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 border border-white/[0.12] shadow-2xl">
            <!-- Left Visual Showcase Panel -->
            <div class="hidden lg:flex lg:col-span-5 relative bg-[#0B0F1A] border-r border-white/[0.08] p-8 flex-col justify-between overflow-hidden">
                <div class="absolute inset-0 pointer-events-none opacity-25">
                    <img src="/images/hero-arena.jpg" alt="" class="w-full h-full object-cover object-center" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0B0F1A] via-[#0B0F1A]/80 to-transparent"></div>
                </div>

                <div class="relative space-y-4">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/15 border border-purple-500/30 text-xs font-bold text-purple-300">
                        🔥 NEW COMPETITOR
                    </span>
                    <h2 class="font-display text-2xl font-extrabold text-white leading-snug">
                        Establish Your Competitive Profile.
                    </h2>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Create your free gamer handle to host private rooms, compete in ranked matchmaking, and unlock seasonal badges.
                    </p>
                </div>

                <div class="relative my-6 rounded-xl overflow-hidden border border-white/[0.12] shadow-lg">
                    <x-game-artwork slug="connect-four" class="w-full h-40" />
                </div>

                <div class="relative space-y-2.5 text-xs text-slate-300 border-t border-white/[0.08] pt-5">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        <span>Instant access to all 4 multiplayer games</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        <span>Global, weekly, and monthly leaderboards</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                        <span>Real-time friend invites and room codes</span>
                    </div>
                </div>
            </div>

            <!-- Right Registration Form -->
            <div class="lg:col-span-7 p-6 sm:p-10">
                <div class="mb-6">
                    <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-white">Create Your Account</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Complete the fields below to initialize your NEXUSARENA profile.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" @submit="submitting = true" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-300 mb-1.5">Display Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Alex Mercer"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border {{ $errors->has('name') ? 'border-rose-500' : 'border-white/[0.14]' }} text-white text-sm focus:outline-none focus:border-orange-500">
                            @error('name')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="username" class="block text-xs font-semibold text-slate-300 mb-1.5">Username (Handle)</label>
                            <input id="username" type="text" name="username" value="{{ old('username') }}" required placeholder="cyber_knight"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border {{ $errors->has('username') ? 'border-rose-500' : 'border-white/[0.14]' }} text-white text-sm focus:outline-none focus:border-orange-500">
                            @error('username')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="player@nexusarena.gg"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-[#080B12] border {{ $errors->has('email') ? 'border-rose-500' : 'border-white/[0.14]' }} text-white text-sm focus:outline-none focus:border-orange-500">
                        @error('email')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
                            <div class="relative">
                                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required placeholder="Min. 8 characters"
                                       class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#080B12] border {{ $errors->has('password') ? 'border-rose-500' : 'border-white/[0.14]' }} text-white text-sm focus:outline-none focus:border-orange-500">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-white cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1.5">Confirm Password</label>
                            <div class="relative">
                                <input id="password_confirmation" :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required placeholder="Repeat password"
                                       class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#080B12] border border-white/[0.14] text-white text-sm focus:outline-none focus:border-orange-500">
                                <button type="button" @click="showConfirm = !showConfirm"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-white cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @error('password')
                        <p class="text-xs text-rose-400">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            :disabled="submitting"
                            class="w-full py-3.5 rounded-xl font-bold text-sm btn-flame disabled:opacity-60 cursor-pointer mt-2">
                        <span x-show="!submitting">Create Gaming Account</span>
                        <span x-show="submitting" x-cloak>Creating Profile...</span>
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-white/[0.08] text-center text-xs text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-orange-400 font-bold hover:text-orange-300 ml-1">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
