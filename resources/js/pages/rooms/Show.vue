<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Copy,
    Crown,
    DoorOpen,
    Flag,
    Gamepad2,
    LoaderCircle,
    Lock,
    LogOut,
    Play,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import ResultBadge from '@/components/ResultBadge.vue';
import type { GameSession } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Rooms', href: '/rooms' },
            { title: 'Room', href: '/rooms' },
        ],
    },
});

type RoomPlayer = {
    user_id: number;
    username: string;
    name: string;
    avatar_url: string;
    is_ready: boolean;
    is_host: boolean;
};

const props = defineProps<{
    room: {
        code: string;
        name: string;
        status: 'waiting' | 'in_progress' | 'finished' | 'closed';
        max_players: number;
        is_private: boolean;
        has_password: boolean;
        game: {
            slug: string;
            name: string;
            icon: string;
            accent: string;
            instructions: string;
        };
        host: { username: string; avatar_url: string };
        players: RoomPlayer[];
        players_count: number;
    };
    session: GameSession | null;
    canJoin: boolean;
    canStart: boolean;
    isMember: boolean;
    isHost: boolean;
    requiresPassword: boolean;
}>();

const joinForm = useForm({ password: '' });
const copied = ref(false);
const pollTimer = ref<number | null>(null);

const isWaiting = computed(() => props.room.status === 'waiting');

const allReady = computed(
    () =>
        props.room.players.length === props.room.max_players &&
        props.room.players.every((player) => player.is_ready),
);

const copyCode = async (): Promise<void> => {
    try {
        await navigator.clipboard.writeText(props.room.code);
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 1800);
    } catch {
        copied.value = false;
    }
};

const join = (): void => {
    joinForm.post(`/rooms/${props.room.code}/join`);
};

const toggleReady = (): void => {
    router.post(
        `/rooms/${props.room.code}/ready`,
        {},
        { preserveScroll: true },
    );
};

const start = (): void => {
    router.post(
        `/rooms/${props.room.code}/start`,
        {},
        { preserveScroll: true },
    );
};

const leave = (): void => {
    if (
        props.room.status === 'in_progress' &&
        !window.confirm(
            'Leaving during a match counts as a forfeit. Leave anyway?',
        )
    ) {
        return;
    }

    router.post(`/rooms/${props.room.code}/leave`);
};

onMounted(() => {
    if (
        props.room.status === 'waiting' ||
        props.room.status === 'in_progress'
    ) {
        pollTimer.value = window.setInterval(() => {
            router.reload({
                only: [
                    'room',
                    'session',
                    'canStart',
                    'canJoin',
                    'isMember',
                    'isHost',
                ],
            });
        }, 4500);
    }
});

onUnmounted(() => {
    if (pollTimer.value !== null) {
        window.clearInterval(pollTimer.value);
    }
});
</script>

<template>
    <Head :title="props.room.name" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Lobby"
            :title="props.room.name"
            :subtitle="`${props.room.game.name} · ${props.room.players_count}/${props.room.max_players} players`"
        >
            <template #actions>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40"
                    @click="copyCode"
                >
                    <Check v-if="copied" class="size-4 text-emerald-300" />
                    <Copy v-else class="size-4" />
                    {{ copied ? 'Copied!' : props.room.code }}
                </button>

                <button
                    v-if="props.isMember"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-2 text-sm font-semibold text-rose-200 transition hover:bg-rose-400/20"
                    @click="leave"
                >
                    <LogOut class="size-4" /> Leave
                </button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="space-y-6">
                <section class="panel p-6">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-12 items-center justify-center rounded-2xl border border-cyan-400/25 bg-cyan-400/10 text-cyan-300"
                            >
                                <GameIcon
                                    :name="props.room.game.icon"
                                    class="size-6"
                                />
                            </div>

                            <div>
                                <p class="font-semibold text-white">
                                    {{ props.room.game.name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Hosted by {{ props.room.host.username }}
                                    <span
                                        v-if="props.room.is_private"
                                        class="text-amber-300"
                                    >
                                        · Private
                                    </span>
                                </p>
                            </div>
                        </div>

                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                            :class="
                                props.room.status === 'in_progress'
                                    ? 'bg-violet-400/15 text-violet-300'
                                    : props.room.status === 'waiting'
                                      ? 'bg-emerald-400/15 text-emerald-300'
                                      : 'bg-white/10 text-slate-400'
                            "
                        >
                            {{ props.room.status.replace('_', ' ') }}
                        </span>
                    </div>

                    <div
                        v-if="props.session && props.isMember"
                        class="mt-5 rounded-xl border border-cyan-400/25 bg-cyan-400/5 p-4"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    Match in progress
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{
                                        props.session.is_draw
                                            ? 'The match ended in a draw.'
                                            : props.session.status ===
                                                'finished'
                                              ? 'The match has finished.'
                                              : 'Your game is live — join the board.'
                                    }}
                                </p>
                            </div>

                            <Link
                                :href="`/games/sessions/${props.session.uuid}`"
                                class="inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-[#06101a]"
                            >
                                <Play class="size-4" /> Open match
                            </Link>
                        </div>
                    </div>

                    <div
                        v-if="props.canJoin"
                        class="mt-5 rounded-xl border border-white/10 bg-white/[0.03] p-4"
                    >
                        <p class="text-sm font-semibold text-white">
                            Join this room
                        </p>

                        <p
                            v-if="props.requiresPassword"
                            class="mt-1 text-xs text-slate-400"
                        >
                            This room is private. Enter the password the host
                            shared with you.
                        </p>

                        <form
                            class="mt-3 flex flex-col gap-2 sm:flex-row"
                            @submit.prevent="join"
                        >
                            <div
                                v-if="props.requiresPassword"
                                class="relative flex-1"
                            >
                                <Lock
                                    class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-500"
                                />
                                <input
                                    v-model="joinForm.password"
                                    type="password"
                                    required
                                    placeholder="Room password"
                                    class="h-10 w-full rounded-xl border border-white/10 bg-white/5 pr-3 pl-9 text-sm text-white outline-none focus:border-cyan-400/50"
                                />
                            </div>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-[#06101a] disabled:opacity-60"
                                :disabled="joinForm.processing"
                            >
                                <LoaderCircle
                                    v-if="joinForm.processing"
                                    class="size-4 animate-spin"
                                />
                                <DoorOpen v-else class="size-4" />
                                Join room
                            </button>
                        </form>

                        <p
                            v-if="
                                joinForm.errors.password || joinForm.errors.room
                            "
                            class="mt-2 text-xs text-rose-300"
                        >
                            {{
                                joinForm.errors.password ?? joinForm.errors.room
                            }}
                        </p>
                    </div>

                    <div
                        v-if="props.isMember && isWaiting"
                        class="mt-5 flex flex-wrap items-center gap-2"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-cyan-400/30 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-200 transition hover:bg-cyan-400/20"
                            @click="toggleReady"
                        >
                            <Check class="size-4" /> Toggle ready
                        </button>

                        <button
                            v-if="props.canStart"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-4 py-2 text-sm font-bold text-[#06101a] transition hover:brightness-110 disabled:opacity-50"
                            :disabled="
                                !allReady ||
                                props.room.players_count !==
                                    props.room.max_players
                            "
                            @click="start"
                        >
                            <Play class="size-4" /> Start match
                        </button>

                        <span
                            v-if="props.canStart && !allReady"
                            class="text-xs text-slate-500"
                        >
                            Every player must be ready and the room must be
                            full.
                        </span>
                    </div>
                </section>

                <section class="panel p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2
                            class="flex items-center gap-2 font-semibold text-white"
                        >
                            <Users class="size-4 text-cyan-300" /> Players
                        </h2>
                        <span class="text-xs text-slate-500">
                            {{ props.room.players_count }}/{{
                                props.room.max_players
                            }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-2">
                        <div
                            v-for="player in props.room.players"
                            :key="player.user_id"
                            class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5"
                        >
                            <PlayerAvatar
                                :src="player.avatar_url"
                                :name="player.name"
                                size="md"
                            />

                            <div class="min-w-0 flex-1">
                                <p
                                    class="flex items-center gap-1.5 truncate text-sm font-medium text-white"
                                >
                                    {{ player.username }}
                                    <Crown
                                        v-if="player.is_host"
                                        class="size-3.5 text-amber-300"
                                    />
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ player.is_host ? 'Host' : 'Player' }}
                                </p>
                            </div>

                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                :class="
                                    player.is_ready
                                        ? 'bg-emerald-400/15 text-emerald-300'
                                        : 'bg-white/10 text-slate-400'
                                "
                            >
                                {{ player.is_ready ? 'Ready' : 'Not ready' }}
                            </span>
                        </div>

                        <div
                            v-for="slot in Math.max(
                                0,
                                props.room.max_players -
                                    props.room.players_count,
                            )"
                            :key="`empty-${slot}`"
                            class="flex items-center gap-3 rounded-xl border border-dashed border-white/10 px-3 py-2.5"
                        >
                            <div
                                class="flex size-10 items-center justify-center rounded-xl border border-dashed border-white/10 text-slate-600"
                            >
                                <Users class="size-4" />
                            </div>
                            <p class="text-sm text-slate-500">
                                Waiting for a player…
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="
                        props.session &&
                        props.isMember &&
                        (props.session.results?.length ?? 0) > 0
                    "
                    class="panel p-6"
                >
                    <h2
                        class="flex items-center gap-2 font-semibold text-white"
                    >
                        <Flag class="size-4 text-amber-300" /> Last result
                    </h2>

                    <div class="mt-3 space-y-2">
                        <div
                            v-for="result in props.session.results"
                            :key="result.id"
                            class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5"
                        >
                            <PlayerAvatar
                                :src="result.user?.avatar_url"
                                :name="result.user?.name ?? 'Player'"
                                size="sm"
                            />

                            <p
                                class="min-w-0 flex-1 truncate text-sm text-white"
                            >
                                {{ result.user?.username ?? 'Player' }}
                            </p>

                            <ResultBadge :result="result.result" />

                            <span
                                class="text-sm font-bold"
                                :class="
                                    result.points_awarded >= 0
                                        ? 'text-emerald-300'
                                        : 'text-rose-300'
                                "
                            >
                                {{ result.points_awarded >= 0 ? '+' : ''
                                }}{{ result.points_awarded }}
                            </span>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <div class="panel p-5">
                    <h2 class="text-sm font-semibold text-white">Room code</h2>
                    <p
                        class="mt-2 font-mono text-2xl font-bold tracking-[0.3em] text-cyan-300"
                    >
                        {{ props.room.code }}
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        Share this code so friends can join from the Rooms page.
                    </p>
                </div>

                <div class="panel p-5">
                    <h2 class="text-sm font-semibold text-white">
                        Lobby rules
                    </h2>
                    <ul class="mt-3 space-y-2 text-xs text-slate-400">
                        <li>
                            The room must be full before the host can start.
                        </li>
                        <li>Every player must toggle Ready.</li>
                        <li>Turns and moves are validated by the server.</li>
                        <li>Leaving a live match counts as a forfeit.</li>
                        <li>Closed rooms cannot be rejoined.</li>
                    </ul>
                </div>

                <div class="panel p-5">
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-white"
                    >
                        <Gamepad2 class="size-4 text-cyan-300" /> How to play
                    </h2>
                    <p class="mt-2 text-xs whitespace-pre-line text-slate-400">
                        {{ props.room.game.instructions }}
                    </p>
                </div>

                <Link
                    :href="`/games/${props.room.game.slug}`"
                    class="panel panel-hover flex items-center gap-2 p-4 text-sm font-medium text-slate-300"
                >
                    <Gamepad2 class="size-4" /> About {{ props.room.game.name }}
                    <ArrowRight class="ml-auto size-4" />
                </Link>
            </aside>
        </div>
    </div>
</template>
