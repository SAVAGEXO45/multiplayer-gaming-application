<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Coins,
    Gamepad2,
    ListChecks,
    LoaderCircle,
    Lock,
    Play,
    Trophy,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import ResultBadge from '@/components/ResultBadge.vue';
import StatTile from '@/components/StatTile.vue';
import type {
    Game,
    GameResultItem,
    LeaderboardEntry,
    Paginator,
} from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Games', href: '/games' }],
    },
});

const props = defineProps<{
    game: Game;
    stats: {
        games_played: number;
        wins: number;
        losses: number;
        draws: number;
        points: number;
        win_rate: number;
    };
    history: Paginator<GameResultItem>;
    topPlayers: LeaderboardEntry[];
    openRooms: Array<{
        code: string;
        name: string;
        is_private: boolean;
        players_count: number;
        max_players: number;
        host: string;
    }>;
}>();

const difficulties = computed(
    () => props.game.difficulties ?? ['easy', 'medium', 'hard'],
);

const difficulty = ref(
    difficulties.value[1] ?? difficulties.value[0] ?? 'medium',
);

const form = useForm({ difficulty: difficulty.value });

const startGame = (): void => {
    form.difficulty = difficulty.value;
    form.post(`/games/${props.game.slug}/start`);
};

const difficultyHint = computed(
    () =>
        ({
            easy: 'Relaxed AI and more attempts. Great for warming up.',
            medium: 'Balanced challenge with standard rules.',
            hard: 'Punishing AI, fewer attempts and bigger rewards.',
        })[difficulty.value] ?? '',
);

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
    <Head :title="props.game.name" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            :eyebrow="
                props.game.supports_multiplayer
                    ? 'Solo & multiplayer'
                    : 'Solo challenge'
            "
            :title="props.game.name"
            :subtitle="props.game.tagline ?? props.game.description"
        >
            <template #actions>
                <Link
                    href="/games"
                    class="rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40 hover:text-cyan-200"
                >
                    Back to games
                </Link>
                <Link
                    v-if="props.game.supports_multiplayer"
                    :href="`/rooms/create?game=${props.game.slug}`"
                    class="inline-flex items-center gap-2 rounded-xl border border-violet-400/30 bg-violet-400/10 px-4 py-2 text-sm font-semibold text-violet-200 transition hover:bg-violet-400/20"
                >
                    <Users class="size-4" /> Create room
                </Link>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                label="Your games"
                :value="props.stats.games_played"
                :icon="Gamepad2"
                accent="cyan"
            />
            <StatTile
                label="Wins"
                :value="props.stats.wins"
                :icon="Trophy"
                accent="emerald"
                :hint="`${props.stats.win_rate}% win rate`"
            />
            <StatTile
                label="Points from game"
                :value="props.stats.points"
                :icon="Coins"
                accent="amber"
            />
            <StatTile
                label="Win reward"
                :value="`+${props.game.points_win}`"
                :icon="Coins"
                accent="violet"
                :hint="`Draw +${props.game.points_draw ?? 0}`"
            />
        </section>

        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
            <div class="space-y-6">
                <section class="panel overflow-hidden">
                    <div class="relative p-6">
                        <div
                            class="pointer-events-none absolute -top-24 -right-10 size-64 rounded-full bg-cyan-400/10 blur-3xl"
                        />
                        <div
                            class="relative flex flex-col gap-5 sm:flex-row sm:items-start"
                        >
                            <div
                                class="flex size-16 shrink-0 items-center justify-center rounded-2xl border border-cyan-400/25 bg-cyan-400/10 text-cyan-300"
                            >
                                <GameIcon
                                    :name="props.game.icon"
                                    class="size-8"
                                />
                            </div>
                            <div class="flex-1">
                                <h2 class="text-xl font-bold text-white">
                                    Start a solo match
                                </h2>
                                <p class="mt-1 text-sm text-slate-400">
                                    Pick a difficulty, then jump straight into
                                    the board. Your opponent is the house AI.
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button
                                        v-for="level in difficulties"
                                        :key="level"
                                        type="button"
                                        class="rounded-xl border px-4 py-2 text-sm font-semibold capitalize transition"
                                        :class="
                                            difficulty === level
                                                ? 'border-cyan-400/60 bg-cyan-400/15 text-cyan-200'
                                                : 'border-white/10 bg-white/5 text-slate-300 hover:border-white/25'
                                        "
                                        @click="difficulty = level"
                                    >
                                        {{ level }}
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ difficultyHint }}
                                </p>

                                <button
                                    type="button"
                                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-5 py-2.5 text-sm font-bold text-[#06101a] transition hover:brightness-110 disabled:opacity-60"
                                    :disabled="form.processing"
                                    @click="startGame"
                                >
                                    <LoaderCircle
                                        v-if="form.processing"
                                        class="size-4 animate-spin"
                                    />
                                    <Play v-else class="size-4" />
                                    Start {{ difficulty }} match
                                </button>
                                <p
                                    v-if="form.errors.difficulty"
                                    class="mt-2 text-xs text-rose-300"
                                >
                                    {{ form.errors.difficulty }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2">
                    <div class="panel p-5">
                        <div class="flex items-center gap-2">
                            <BookOpen class="size-4 text-cyan-300" />
                            <h2 class="font-semibold text-white">
                                Instructions
                            </h2>
                        </div>
                        <p
                            class="mt-3 text-sm whitespace-pre-line text-slate-400"
                        >
                            {{ props.game.instructions }}
                        </p>
                    </div>
                    <div class="panel p-5">
                        <div class="flex items-center gap-2">
                            <ListChecks class="size-4 text-violet-300" />
                            <h2 class="font-semibold text-white">Rules</h2>
                        </div>
                        <p
                            class="mt-3 text-sm whitespace-pre-line text-slate-400"
                        >
                            {{ props.game.rules }}
                        </p>
                    </div>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">
                            Your match history
                        </h2>
                        <span class="text-xs text-slate-500">
                            {{ props.history.total }} matches
                        </span>
                    </div>

                    <div
                        v-if="props.history.data.length"
                        class="mt-4 overflow-x-auto"
                    >
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr
                                    class="text-left text-xs text-slate-500 uppercase"
                                >
                                    <th class="pb-2 font-medium">Result</th>
                                    <th class="pb-2 font-medium">Opponent</th>
                                    <th class="pb-2 font-medium">Score</th>
                                    <th class="pb-2 font-medium">Points</th>
                                    <th class="pb-2 text-right font-medium">
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr
                                    v-for="result in props.history.data"
                                    :key="result.id"
                                    class="text-slate-300"
                                >
                                    <td class="py-2.5">
                                        <ResultBadge :result="result.result" />
                                    </td>
                                    <td class="py-2.5">
                                        <span v-if="result.opponent">
                                            {{ result.opponent }}
                                        </span>
                                        <span v-else class="text-slate-500"
                                            >AI</span
                                        >
                                        <span
                                            v-if="result.is_multiplayer"
                                            class="ml-1 text-[10px] text-violet-300"
                                        >
                                            MP
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-slate-400">
                                        {{ result.score }}
                                    </td>
                                    <td
                                        class="py-2.5 font-semibold"
                                        :class="
                                            result.points_awarded >= 0
                                                ? 'text-emerald-300'
                                                : 'text-rose-300'
                                        "
                                    >
                                        {{
                                            result.points_awarded >= 0
                                                ? '+'
                                                : ''
                                        }}{{ result.points_awarded }}
                                    </td>
                                    <td
                                        class="py-2.5 text-right text-xs text-slate-500"
                                    >
                                        {{ formatDate(result.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-500">
                        No matches yet. Your results will appear here.
                    </p>

                    <PaginationLinks class="mt-4" :paginator="props.history" />
                </section>
            </div>

            <div class="space-y-6">
                <section class="panel p-5">
                    <div class="flex items-center gap-2">
                        <Trophy class="size-4 text-amber-300" />
                        <h2 class="font-semibold text-white">Game leaders</h2>
                    </div>
                    <ol class="mt-3 divide-y divide-white/5">
                        <li
                            v-for="player in props.topPlayers"
                            :key="player.user_id"
                            class="flex items-center gap-3 py-2.5"
                        >
                            <span class="w-4 text-xs font-bold text-slate-500">
                                {{ player.rank }}
                            </span>
                            <PlayerAvatar
                                :src="player.avatar_url"
                                :name="player.name"
                                size="sm"
                            />
                            <Link
                                :href="`/players/${player.username}`"
                                class="min-w-0 flex-1 truncate text-sm text-slate-300 hover:text-cyan-200"
                            >
                                {{ player.username }}
                            </Link>
                            <span class="text-xs font-bold text-cyan-300">
                                {{ player.points }}
                            </span>
                        </li>
                    </ol>
                    <p
                        v-if="props.topPlayers.length === 0"
                        class="mt-3 text-sm text-slate-500"
                    >
                        No ranked players yet.
                    </p>
                </section>

                <section
                    v-if="props.game.supports_multiplayer"
                    class="panel p-5"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <Users class="size-4 text-violet-300" />
                            <h2 class="font-semibold text-white">Open rooms</h2>
                        </div>
                        <Link
                            href="/rooms"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            All rooms
                        </Link>
                    </div>
                    <div v-if="props.openRooms.length" class="mt-3 space-y-2">
                        <Link
                            v-for="room in props.openRooms"
                            :key="room.code"
                            :href="`/rooms/${room.code}`"
                            class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5 transition hover:border-violet-400/40"
                        >
                            <div class="min-w-0">
                                <p
                                    class="flex items-center gap-1 truncate text-sm font-medium text-white"
                                >
                                    <Lock
                                        v-if="room.is_private"
                                        class="size-3 text-amber-300"
                                    />
                                    {{ room.name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ room.host }} ·
                                    {{ room.players_count }}/{{
                                        room.max_players
                                    }}
                                </p>
                            </div>
                            <ArrowRight
                                class="size-4 shrink-0 text-slate-400"
                            />
                        </Link>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        No open rooms. Create one and invite a friend.
                    </p>
                </section>

                <section class="panel p-5">
                    <h2 class="font-semibold text-white">Reward breakdown</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-400">Win</dt>
                            <dd class="font-semibold text-emerald-300">
                                +{{ props.game.points_win }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-400">Draw</dt>
                            <dd class="font-semibold text-amber-300">
                                +{{ props.game.points_draw ?? 0 }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-400">Loss (multiplayer)</dt>
                            <dd class="font-semibold text-rose-300">
                                {{ props.game.points_loss ?? 0 }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-400">Participation</dt>
                            <dd class="font-semibold text-slate-300">
                                +{{ props.game.points_participation ?? 0 }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>
</template>
