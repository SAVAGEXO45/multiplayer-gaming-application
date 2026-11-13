<x-layouts.app title="Password Recovery — NEXUSARENA">
    <div class="max-w-md mx-auto px-4 py-12 lg:py-20" x-data="{ submitting: false }">
        <div class="surface-card rounded-2xl p-6 sm:p-8">
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold mb-4">
                Account Security
            </div>
            <h1 class="font-display text-2xl font-bold text-white tracking-tight">Reset your password</h1>
            <p class="text-sm text-slate-400 mt-1.5 mb-6">Enter your registered email address and we will generate a secure password recovery link.</p>

            <form method="POST" action="{{ route('password.email') }}" @submit="submitting = true" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="player@nexusarena.gg"
                           class="w-full px-4 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-white text-sm placeholder-slate-600 focus:outline-none focus:border-blue-500 transition">
                    @error('email')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" :disabled="submitting"
                        class="w-full py-3 rounded-lg font-semibold text-sm bg-blue-600 hover:bg-blue-500 disabled:opacity-60 text-white shadow-sm transition cursor-pointer">
                    <span x-show="!submitting">Generate Reset Link</span>
                    <span x-show="submitting" x-cloak>Sending Recovery Link...</span>
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-[#1E2738] text-center">
                <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition">
                    &larr; Return to Sign In
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
