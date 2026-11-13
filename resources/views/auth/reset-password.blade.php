<x-layouts.app title="Set New Password — NEXUSARENA">
    <div class="max-w-md mx-auto px-4 py-12 lg:py-20" x-data="{ showPassword: false, submitting: false }">
        <div class="surface-card rounded-2xl p-6 sm:p-8">
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold mb-4">
                Credentials Update
            </div>
            <h1 class="font-display text-2xl font-bold text-white tracking-tight">Set a new password</h1>
            <p class="text-sm text-slate-400 mt-1.5 mb-6">Choose a strong new password for your NEXUSARENA account.</p>

            <form method="POST" action="{{ route('password.update') }}" @submit="submitting = true" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required
                           class="w-full px-4 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-white text-sm focus:outline-none focus:border-blue-500 transition">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300">New Password</label>
                        <button type="button" @click="showPassword = !showPassword" class="text-[11px] text-slate-400 hover:text-white cursor-pointer" x-text="showPassword ? 'Hide' : 'Show'"></button>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'" name="password" required
                           class="w-full px-4 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-white text-sm focus:outline-none focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Confirm New Password</label>
                    <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" required
                           class="w-full px-4 py-2.5 rounded-lg bg-[#0D111A] border border-[#263147] text-white text-sm focus:outline-none focus:border-blue-500 transition">
                </div>

                <button type="submit" :disabled="submitting"
                        class="w-full py-3 rounded-lg font-semibold text-sm bg-blue-600 hover:bg-blue-500 disabled:opacity-60 text-white shadow-sm transition cursor-pointer">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
