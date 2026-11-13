<x-layouts.app title="Notifications Center — NEXUSARENA">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold mb-2">
                    Real-Time Alerts
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-bold text-white tracking-tight">Notifications Center</h1>
            </div>

            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg text-xs font-semibold bg-[#161C2B] hover:bg-[#1E2638] text-slate-200 border border-[#263147] transition cursor-pointer">
                    Mark All as Read
                </button>
            </form>
        </div>

        <div class="surface-card rounded-xl divide-y divide-[#1E2738] overflow-hidden">
            @forelse($notifications as $item)
                <div class="p-5 flex items-start justify-between gap-4 {{ $item->isRead() ? 'bg-[#0D111A]/60 opacity-75' : 'bg-blue-500/[0.04]' }}">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            @if(!$item->isRead())
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            @endif
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-400">{{ str_replace('_', ' ', $item->type) }}</span>
                            <span class="text-xs text-slate-500">&bull; {{ $item->created_at?->diffForHumans() }}</span>
                        </div>
                        <h3 class="text-sm font-semibold text-white">{{ $item->title }}</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $item->message }}</p>
                    </div>

                    <form method="POST" action="{{ route('notifications.read', $item->id) }}">
                        @csrf
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shrink-0 transition cursor-pointer">
                            {{ $item->action_url ? 'Open' : 'Dismiss' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-400">
                    Your notification feed is clear.
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
