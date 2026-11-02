<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { DoorClosed, Gamepad2, Lock, Users } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import type { Paginator } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Rooms', href: '/admin/rooms' },
        ],
    },
});

type AdminRoom = {
    code: string;
    name: string;
    status: 'waiting' | 'in_progress' | 'finished' | 'closed';
    is_private: boolean;
    game: string;
    host: string;
    players_count: number;
    max_players: number;
    created_at: string | null;
};

const props = defineProps<{
    rooms: Paginator<AdminRoom>;
    status: string | null;
}>();

const statuses = [
    { value: '', label: 'All statuses' },
    { value: 'waiting', label: 'Waiting' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'finished', label: 'Finished' },
    { value: 'closed', label: 'Closed' },
];

const filter = (value: string): void => {
    router.get(
        '/admin/rooms',
        { status: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const closeRoom = (room: AdminRoom): void => {
    if (
        !window.confirm(
            `Close room "${room.name}"? Any active match will be abandoned.`,
        )
    ) {
        return;
    }

    router.patch(
        `/admin/rooms/${room.code}/close`,
        {},
        { preserveScroll: true },
    );
};

const statusTone: Record<string, string> = {
    waiting: 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300',
    in_progress: 'border-violet-400/30 bg-violet-400/10 text-violet-300',
    finished: 'border-cyan-400/30 bg-cyan-400/10 text-cyan-300',
    closed: 'border-white/10 bg-white/5 text-slate-400',
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
    <Head title="Admin — Rooms" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Rooms"
            subtitle="Monitor lobbies and live matches, and close rooms that need to stop."
        />

        <section class="panel flex flex-wrap items-center gap-2 p-3">
            <button
                v-for="option in statuses"
                :key="option.value"
                type="button"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                :class="
                    (props.status ?? '') === option.value
                        ? 'bg-cyan-400/15 text-cyan-200'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white'
                "
                @click="filter(option.value)"
            >
                {{ option.label }}
            </button>
        </section>

        <section class="table-shell">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="border-b border-white/10 bg-white/[0.03]">
                        <tr class="text-left text-xs text-slate-500 uppercase">
                            <th class="px-4 py-3 font-medium">Room</th>
                            <th class="px-4 py-3 font-medium">Game</th>
                            <th class="px-4 py-3 font-medium">Host</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Players
                            </th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Created</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="room in props.rooms.data"
                            :key="room.code"
                            class="transition hover:bg-white/[0.03]"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-white">
                                        {{ room.name }}
                                    </p>
                                    <Lock
                                        v-if="room.is_private"
                                        class="size-3 text-amber-300"
                                    />
                                </div>
                                <p
                                    class="font-mono text-xs tracking-widest text-slate-500"
                                >
                                    {{ room.code }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                <span class="inline-flex items-center gap-1.5">
                                    <Gamepad2 class="size-3.5 text-cyan-300" />
                                    {{ room.game }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ room.host }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-300">
                                <span class="inline-flex items-center gap-1">
                                    <Users class="size-3.5 text-slate-500" />
                                    {{ room.players_count }}/{{
                                        room.max_players
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase"
                                    :class="statusTone[room.status]"
                                >
                                    {{ room.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ formatDate(room.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="room.status !== 'closed'"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-rose-400/30 bg-rose-400/10 px-2.5 py-1.5 text-xs font-medium text-rose-300 transition hover:bg-rose-400/20"
                                    @click="closeRoom(room)"
                                >
                                    <DoorClosed class="size-3.5" /> Close
                                </button>
                                <span v-else class="text-xs text-slate-500"
                                    >Closed</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p
                v-if="props.rooms.data.length === 0"
                class="px-4 py-10 text-center text-sm text-slate-500"
            >
                No rooms found for this filter.
            </p>
        </section>

        <PaginationLinks :paginator="props.rooms" />
    </div>
</template>
