<x-layouts.app title="Sign In">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
         x-data="{
            email: '{{ old('email') }}',
            password: '',
            showPassword: false,
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
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/15 border border-orange-500/30 text-xs font-bold text-orange-300">
                        ⚡ NEXUSARENA ACCOUNT
                    </span>
                    <h2 class="font-display text-2xl font-extrabold text-white leading-snug">
                        Return to the Competitive Queue.
                    </h2>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Sign in to access real-time multiplayer lobbies, seasonal tournaments, verified rankings, and your personal match history.
                    </p>
                </div>

                <!-- Tactical Illustration -->
                <div class="relative my-6 rounded-xl overflow-hidden border border-white/[0.12] shadow-lg">
                    <x-game-artwork slug="chess" class="w-full h-40" />
                </div>

                <!-- Quick Demo Login Switcher -->
                <div class="relative p-4 rounded-xl bg-[#121726]/95 border border-white/[0.1] space-y-2.5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-orange-400">Instant Demo Credentials</div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                @click="email = 'admin@nexusarena.gg'; password = 'password123'"
                                class="px-3 py-2 rounded-lg bg-purple-500/15 hover:bg-purple-500/25 border border-purple-500/30 text-xs font-bold text-purple-300 transition cursor-pointer">
                            Fill Admin Demo
                        </button>
                        <button type="button"
                                @click="email = 'viper@nexusarena.gg'; password = 'password123'"
                                class="px-3 py-2 rounded-lg bg-sky-500/15 hover:bg-sky-500/25 border border-sky-500/30 text-xs font-bold text-sky-300 transition cursor-pointer">
                            Fill Player #1
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Form Panel -->
            <div class="lg:col-span-7 p-6 sm:p-10 flex flex-col justify-center">
                <div class="mb-6">
                    <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-white">Sign In to Your Account</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Enter your registered email address or gamer handle below.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" @submit="submitting = true" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">Email or Username</label>
                        <input id="email" type="text" name="email" x-model="email" required autofocus
                               placeholder="viper@nexusarena.gg or cyber_viper"
                               class="w-full px-4 py-3 rounded-xl bg-[#080B12] border {{ $errors->has('email') ? 'border-rose-500' : 'border-white/[0.14]' }} text-white text-sm focus:outline-none focus:border-orange-500 transition">
                        @error('email')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-slate-300">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-orange-400 hover:text-orange-300">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required
                                   placeholder="Enter your password"
                                   class="w-full pl-4 pr-10 py-3 rounded-xl bg-[#080B12] border {{ $errors->has('password') ? 'border-rose-500' : 'border-white/[0.14]' }} text-white text-sm focus:outline-none focus:border-orange-500 transition">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-white cursor-pointer"
                                    title="Toggle Password Visibility">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded bg-[#080B12] border-white/20 text-orange-500 focus:ring-orange-500">
                            <span>Remember this device</span>
                        </label>
                    </div>

                    <button type="submit"
                            :disabled="submitting"
                            class="w-full py-3.5 rounded-xl font-bold text-sm btn-flame disabled:opacity-60 cursor-pointer flex items-center justify-center gap-2">
                        <span x-show="!submitting">Sign In to Arena</span>
                        <span x-show="submitting" x-cloak>Authenticating...</span>
                    </button>
                </form>

                <!-- Mobile Demo Credentials Bar -->
                <div class="lg:hidden mt-6 pt-5 border-t border-white/[0.08]">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-orange-400 mb-2">Quick Demo Credentials</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                @click="email = 'admin@nexusarena.gg'; password = 'password123'"
                                class="px-3 py-2 rounded-lg bg-purple-500/15 border border-purple-500/30 text-xs font-bold text-purple-300">
                            Fill Admin Demo
                        </button>
                        <button type="button"
                                @click="email = 'viper@nexusarena.gg'; password = 'password123'"
                                class="px-3 py-2 rounded-lg bg-sky-500/15 border border-sky-500/30 text-xs font-bold text-sky-300">
                            Fill Player #1
                        </button>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-white/[0.08] text-center text-xs text-slate-400">
                    Don't have a NEXUSARENA account?
                    <a href="{{ route('register') }}" class="text-orange-400 font-bold hover:text-orange-300 ml-1">Create Account</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
