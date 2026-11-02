<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Gamepad2, Lock, Plus, Users } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import type { Paginator } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Rooms', href: '/rooms' }],
    },
});

type Room = {
    code: string;
    name: string;
    status: 'waiting' | 'in_progress' | 'finished' | 'closed';
    max_players: number;
    is_private: boolean;
    game: { name: string; slug: string; icon: string; accent: string };
    host: string;
    players_count: number;
    is_member: boolean;
};

type MyRoom = {
    code: string;
    name: string;
    status: string;
    game: { name: string; slug: string; icon: string };
    players_count: number;
    max_players: number;
};

const props = defineProps<{
    rooms: Paginator<Room>;
    myRooms: MyRoom[];
    games: Array<{
        id: number;
        slug: string;
        name: string;
        icon: string;
        accent: string;
        min_players: number;
        max_players: number;
    }>;
}>();

const join = (room: Room): void => {
    router.post(`/rooms/${room.code}/join`, {}, { preserveScroll: true });
};

const statusStyle = (status: string): string =>
    status === 'in_progress'
        ? 'bg-violet-400/15 text-violet-300'
        : 'bg-emerald-400/15 text-emerald-300';
</script>

<template>
    <Head title="Rooms" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Multiplayer lobby"
            title="Rooms"
            subtitle="Create a private match, share the code and ready up with friends."
        >
            <template #actions>
                <Link
                    href="/rooms/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-4 py-2 text-sm font-bold text-[#06101a] transition hover:brightness-110"
                >
                    <Plus class="size-4" /> Create room
                </Link>
            </template>
        </PageHeader>

        <section v-if="props.myRooms.length" class="panel p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 font-semibold text-white">
                    <Gamepad2 class="size-4 text-cyan-300" /> Your active rooms
                </h2>
                <span class="text-xs text-slate-500">{{
                    props.myRooms.length
                }}</span>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="room in props.myRooms"
                    :key="room.code"
                    :href="`/rooms/${room.code}`"
                    class="group rounded-xl border border-cyan-400/20 bg-cyan-400/5 p-4 transition hover:border-cyan-400/40 hover:bg-cyan-400/10"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div
                            class="flex size-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-cyan-300"
                        >
                            <GameIcon :name="room.game.icon" class="size-5" />
                        </div>
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                            :class="statusStyle(room.status)"
                        >
                            {{ room.status.replace('_', ' ') }}
                        </span>
                    </div>
                    <p class="mt-3 truncate font-semibold text-white">
                        {{ room.name }}
                    </p>
                    <p class="text-xs text-slate-400">
                        {{ room.game.name }} · {{ room.players_count }}/{{
                            room.max_players
                        }}
                        players
                    </p>
                    <span
                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-cyan-300"
                    >
                        Enter room <ArrowRight class="size-3.5" />
                    </span>
                </Link>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-white">Open rooms</h2>
                <span class="text-xs text-slate-500">
                    {{ props.rooms.total }} available
                </span>
            </div>

            <div
                v-if="props.rooms.data.length"
                class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="room in props.rooms.data"
                    :key="room.code"
                    class="panel panel-hover flex flex-col p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div
                            class="flex size-11 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-cyan-300"
                        >
                            <GameIcon :name="room.game.icon" class="size-5" />
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="room.is_private"
                                class="inline-flex items-center gap-1 rounded-full border border-amber-400/30 bg-amber-400/10 px-2 py-0.5 text-[10px] font-semibold text-amber-300"
                            >
                                <Lock class="size-3" /> Private
                            </span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                :class="statusStyle(room.status)"
                            >
                                {{ room.status.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 flex-1">
                        <h3 class="truncate font-semibold text-white">
                            {{ room.name }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            {{ room.game.name }} · host {{ room.host }}
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <span class="stat-chip">
                            <Users class="size-3" />
                            {{ room.players_count }}/{{ room.max_players }}
                        </span>

                        <Link
                            v-if="room.is_member"
                            :href="`/rooms/${room.code}`"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-cyan-400 px-3 py-1.5 text-xs font-bold text-[#06101a]"
                        >
                            Enter <ArrowRight class="size-3.5" />
                        </Link>
                        <button
                            v-else-if="
                                room.status === 'waiting' &&
                                room.players_count < room.max_players
                            "
                            type="button"
                            class="rounded-lg border border-cyan-400/30 bg-cyan-400/10 px-3 py-1.5 text-xs font-semibold text-cyan-200 transition hover:bg-cyan-400/20"
                            @click="join(room)"
                        >
                            {{ room.is_private ? 'Enter password' : 'Join' }}
                        </button>
                        <span v-else class="text-xs text-slate-500">
                            {{
                                room.status === 'in_progress'
                                    ? 'In progress'
                                    : 'Full'
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <EmptyState
                v-else
                title="No open rooms"
                description="Be the first to open a lobby and invite a rival."
            >
                <Link
                    href="/rooms/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-cyan-400 px-4 py-2 text-xs font-bold text-[#06101a]"
                >
                    <Plus class="size-3.5" /> Create a room
                </Link>
            </EmptyState>

            <PaginationLinks :paginator="props.rooms" />
        </section>
    </div>
</template>
