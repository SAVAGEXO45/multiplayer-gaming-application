<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Crown, Medal, Target, Trophy, Users } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import type { LeaderboardEntry } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Leaderboard', href: '/leaderboard' }],
    },
});

const props = defineProps<{
    entries: LeaderboardEntry[];
    period: 'all' | 'weekly' | 'monthly';
    game: string | null;
    playerRank: number | null;
    games: Array<{
        id: number;
        slug: string;
        name: string;
        icon: string;
        accent: string;
    }>;
}>();

const periods = [
    { value: 'all', label: 'All time' },
    { value: 'weekly', label: 'This week' },
    { value: 'monthly', label: 'This month' },
] as const;

const applyFilters = (nextPeriod: string, nextGame: string | null): void => {
    router.get(
        '/leaderboard',
        {
            period: nextPeriod,
            game: nextGame || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const setPeriod = (value: string): void => applyFilters(value, props.game);

const setGame = (event: Event): void => {
    const value = (event.target as HTMLSelectElement).value;

    applyFilters(props.period, value === '' ? null : value);
};

const podium = computed(() => props.entries.slice(0, 3));
const rest = computed(() => props.entries.slice(3));

const number = (value: number): string => new Intl.NumberFormat().format(value);

const medalTone = (rank: number): string =>
    rank === 1
        ? 'from-amber-300/25 to-amber-500/5 border-amber-300/30'
        : rank === 2
          ? 'from-slate-300/20 to-slate-500/5 border-slate-300/30'
          : 'from-orange-400/20 to-orange-600/5 border-orange-400/30';
</script>

<template>
    <Head title="Leaderboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Hall of fame"
            title="Leaderboard"
            subtitle="Rankings are computed on the server from verified match results."
        >
            <template #actions>
                <span
                    v-if="props.playerRank"
                    class="inline-flex items-center gap-2 rounded-xl border border-cyan-400/30 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-200"
                >
                    <Trophy class="size-4" /> Your rank: #{{ props.playerRank }}
                </span>
                <span
                    v-else
                    class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-400"
                >
                    Play a match to get ranked
                </span>
            </template>
        </PageHeader>

        <section
            class="panel flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="option in periods"
                    :key="option.value"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    :class="
                        props.period === option.value
                            ? 'bg-cyan-400/15 text-cyan-200'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white'
                    "
                    @click="setPeriod(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>

            <div class="flex items-center gap-2">
                <label for="game-filter" class="text-xs text-slate-500"
                    >Game</label
                >
                <select
                    id="game-filter"
                    :value="props.game ?? ''"
                    class="h-9 rounded-lg border border-white/10 bg-[#0b1020] px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                    @change="setGame"
                >
                    <option value="">All games</option>
                    <option
                        v-for="game in props.games"
                        :key="game.slug"
                        :value="game.slug"
                    >
                        {{ game.name }}
                    </option>
                </select>
            </div>
        </section>

        <section v-if="props.entries.length" class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="player in podium"
                :key="player.user_id"
                class="panel relative overflow-hidden border bg-gradient-to-b p-5 text-center"
                :class="medalTone(player.rank)"
            >
                <div class="flex justify-center">
                    <Crown
                        v-if="player.rank === 1"
                        class="size-5 text-amber-300"
                    />
                    <Medal v-else class="size-5 text-slate-300" />
                </div>
                <p class="mt-1 text-xs font-semibold text-slate-400">
                    #{{ player.rank }}
                </p>
                <div class="mt-3 flex justify-center">
                    <PlayerAvatar
                        :src="player.avatar_url"
                        :name="player.name"
                        size="lg"
                    />
                </div>
                <Link
                    :href="`/players/${player.username}`"
                    class="mt-3 block truncate font-semibold text-white hover:text-cyan-200"
                >
                    {{ player.username }}
                </Link>
                <p class="text-xs text-slate-500">
                    {{ player.wins }}W · {{ player.win_rate }}% win rate
                </p>
                <p class="mt-3 text-2xl font-black text-cyan-300">
                    {{ number(player.points) }}
                </p>
                <p class="text-[10px] tracking-widest text-slate-500 uppercase">
                    Points
                </p>
            </div>
        </section>

        <section v-if="props.entries.length" class="table-shell">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="border-b border-white/10 bg-white/[0.03]">
                        <tr class="text-left text-xs text-slate-500 uppercase">
                            <th class="px-4 py-3 font-medium">Rank</th>
                            <th class="px-4 py-3 font-medium">Player</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Points
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Wins
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Losses
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Draws
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Games
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Win %
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Streak
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="player in rest"
                            :key="player.user_id"
                            class="transition hover:bg-white/[0.03]"
                        >
                            <td class="px-4 py-3 text-slate-500">
                                #{{ player.rank }}
                            </td>
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/players/${player.username}`"
                                    class="flex items-center gap-3"
                                >
                                    <PlayerAvatar
                                        :src="player.avatar_url"
                                        :name="player.name"
                                        size="sm"
                                    />
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-medium text-white"
                                        >
                                            {{ player.username }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-slate-500"
                                        >
                                            {{ player.name }}
                                        </p>
                                    </div>
                                </Link>
                            </td>
                            <td
                                class="px-4 py-3 text-right font-bold text-cyan-300"
                            >
                                {{ number(player.points) }}
                            </td>
                            <td class="px-4 py-3 text-right text-emerald-300">
                                {{ player.wins }}
                            </td>
                            <td class="px-4 py-3 text-right text-rose-300">
                                {{ player.losses }}
                            </td>
                            <td class="px-4 py-3 text-right text-amber-300">
                                {{ player.draws }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-300">
                                {{ player.games_played }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-300">
                                {{ player.win_rate }}%
                            </td>
                            <td class="px-4 py-3 text-right text-slate-400">
                                {{ player.best_streak }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <EmptyState
            v-else
            title="No ranked players yet"
            description="The first verified match will start filling this board."
        >
            <Link
                href="/games"
                class="inline-flex items-center gap-2 rounded-lg bg-cyan-400 px-4 py-2 text-xs font-bold text-[#06101a]"
            >
                <Target class="size-3.5" /> Play a game
            </Link>
        </EmptyState>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="panel p-4">
                <p
                    class="flex items-center gap-2 text-sm font-semibold text-white"
                >
                    <Users class="size-4 text-cyan-300" /> Ranked players
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ props.entries.length }} listed for this filter.
                </p>
            </div>
            <div class="panel p-4">
                <p
                    class="flex items-center gap-2 text-sm font-semibold text-white"
                >
                    <Trophy class="size-4 text-amber-300" /> Points source
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Only server-recorded results count towards rankings.
                </p>
            </div>
            <div class="panel p-4">
                <p
                    class="flex items-center gap-2 text-sm font-semibold text-white"
                >
                    <Medal class="size-4 text-violet-300" /> Fair play
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Suspended accounts are excluded automatically.
                </p>
            </div>
        </section>
    </div>
</template>
