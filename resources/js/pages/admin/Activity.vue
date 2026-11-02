<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Radar, Search, ShieldAlert, Trophy } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ResultBadge from '@/components/ResultBadge.vue';
import type { Paginator } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Activity', href: '/admin/activity' },
        ],
    },
});

type SecurityEventItem = {
    id: number;
    type: string;
    severity: string;
    description: string;
    ip_address: string | null;
    user: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
};

type ActivityResult = {
    id: number;
    user: string;
    game: string;
    result: string;
    points_awarded: number;
    is_multiplayer: boolean;
    created_at: string | null;
};

const props = defineProps<{
    events: Paginator<SecurityEventItem>;
    results: Paginator<ActivityResult>;
    filters: {
        severity: string | null;
        type: string;
    };
}>();

const severity = ref(props.filters.severity ?? '');
const type = ref(props.filters.type ?? '');

const applyFilters = (): void => {
    router.get(
        '/admin/activity',
        {
            severity: severity.value || undefined,
            type: type.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetFilters = (): void => {
    severity.value = '';
    type.value = '';
    applyFilters();
};

const severityTone: Record<string, string> = {
    high: 'border-rose-400/30 bg-rose-400/10 text-rose-300',
    medium: 'border-amber-400/30 bg-amber-400/10 text-amber-300',
    low: 'border-cyan-400/30 bg-cyan-400/10 text-cyan-300',
};

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString(undefined, {
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';
</script>

<template>
    <Head title="Admin — Activity" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Activity & security"
            subtitle="Audit suspicious events and review every verified match result."
        />

        <section class="panel p-4">
            <form
                class="grid gap-3 sm:grid-cols-[auto_1fr_auto_auto]"
                @submit.prevent="applyFilters"
            >
                <select
                    v-model="severity"
                    class="h-10 rounded-xl border border-white/10 bg-[#0b1020] px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                >
                    <option value="">All severities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-500"
                    />
                    <input
                        v-model="type"
                        type="search"
                        placeholder="Filter by event type (e.g. admin, score)"
                        class="h-10 w-full rounded-xl border border-white/10 bg-white/5 pr-3 pl-9 text-sm text-white outline-none focus:border-cyan-400/50"
                    />
                </div>
                <button
                    type="submit"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-cyan-400 px-4 text-sm font-bold text-[#06101a]"
                >
                    Filter
                </button>
                <button
                    type="button"
                    class="h-10 rounded-xl border border-white/10 bg-white/5 px-3 text-sm text-slate-300"
                    @click="resetFilters"
                >
                    Reset
                </button>
            </form>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2
                    class="flex items-center gap-2 text-lg font-semibold text-white"
                >
                    <ShieldAlert class="size-5 text-amber-300" /> Security
                    events
                </h2>
                <span class="text-xs text-slate-500"
                    >{{ props.events.total }} events</span
                >
            </div>

            <div class="table-shell">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="border-b border-white/10 bg-white/[0.03]">
                            <tr
                                class="text-left text-xs text-slate-500 uppercase"
                            >
                                <th class="px-4 py-3 font-medium">Event</th>
                                <th class="px-4 py-3 font-medium">Severity</th>
                                <th class="px-4 py-3 font-medium">User</th>
                                <th class="px-4 py-3 font-medium">IP</th>
                                <th class="px-4 py-3 text-right font-medium">
                                    When
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr
                                v-for="event in props.events.data"
                                :key="event.id"
                                class="transition hover:bg-white/[0.03]"
                            >
                                <td class="px-4 py-3">
                                    <p class="font-mono text-xs text-cyan-300">
                                        {{ event.type }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ event.description }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase"
                                        :class="
                                            severityTone[event.severity] ??
                                            severityTone.low
                                        "
                                    >
                                        {{ event.severity }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-300">
                                    {{ event.user ?? 'System' }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-xs text-slate-500"
                                >
                                    {{ event.ip_address ?? '—' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-xs text-slate-500"
                                >
                                    {{ formatDate(event.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-if="props.events.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-slate-500"
                >
                    No security events match this filter.
                </p>
            </div>

            <PaginationLinks :paginator="props.events" />
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2
                    class="flex items-center gap-2 text-lg font-semibold text-white"
                >
                    <Trophy class="size-5 text-cyan-300" /> Match results
                </h2>
                <span class="text-xs text-slate-500"
                    >{{ props.results.total }} results</span
                >
            </div>

            <div class="table-shell">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead class="border-b border-white/10 bg-white/[0.03]">
                            <tr
                                class="text-left text-xs text-slate-500 uppercase"
                            >
                                <th class="px-4 py-3 font-medium">Player</th>
                                <th class="px-4 py-3 font-medium">Game</th>
                                <th class="px-4 py-3 font-medium">Result</th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Points
                                </th>
                                <th class="px-4 py-3 text-right font-medium">
                                    When
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr
                                v-for="result in props.results.data"
                                :key="result.id"
                                class="transition hover:bg-white/[0.03]"
                            >
                                <td class="px-4 py-3 text-slate-300">
                                    {{ result.user }}
                                </td>
                                <td class="px-4 py-3 text-slate-300">
                                    {{ result.game }}
                                    <span
                                        v-if="result.is_multiplayer"
                                        class="ml-1 text-[10px] text-violet-300"
                                    >
                                        MP
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <ResultBadge :result="result.result" />
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-semibold"
                                    :class="
                                        result.points_awarded >= 0
                                            ? 'text-emerald-300'
                                            : 'text-rose-300'
                                    "
                                >
                                    {{ result.points_awarded >= 0 ? '+' : ''
                                    }}{{ result.points_awarded }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-xs text-slate-500"
                                >
                                    {{ formatDate(result.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-if="props.results.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-slate-500"
                >
                    No match results yet.
                </p>
            </div>

            <PaginationLinks :paginator="props.results" />
        </section>

        <section
            class="panel flex items-start gap-3 p-4 text-xs text-slate-400"
        >
            <Radar class="mt-0.5 size-4 shrink-0 text-violet-300" />
            <p>
                Security events include failed score tampering attempts, rate
                limit hits, admin actions and suspicious request patterns.
            </p>
        </section>
    </div>
</template>
