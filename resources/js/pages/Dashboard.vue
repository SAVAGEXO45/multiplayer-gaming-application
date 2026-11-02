<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bell,
    Coins,
    Flame,
    Gamepad2,
    Gift,
    Medal,
    Trophy,
    TrendingUp,
    Users,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import ResultBadge from '@/components/ResultBadge.vue';
import StatTile from '@/components/StatTile.vue';
import type { Game, GameResultItem, LeaderboardEntry } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
    },
});

type Stats = {
    games_played: number;
    wins: number;
    losses: number;
    draws: number;
    points: number;
    current_streak: number;
    best_streak: number;
    multiplayer_wins: number;
    win_rate: number;
    rank: number | null;
};

type ActiveRoom = {
    code: string;
    name: string;
    status: string;
    game: { name: string; slug: string };
    players_count: number;
    max_players: number;
};

type LatestAchievement = {
    name: string;
    icon: string;
    tier: string;
    unlocked_at: string | null;
};

const props = defineProps<{
    stats: Stats;
    recentGames: GameResultItem[];
    topPlayers: LeaderboardEntry[];
    activeRooms: ActiveRoom[];
    games: Game[];
    achievements: {
        unlocked: number;
        total: number;
        latest: LatestAchievement[];
    };
    pointsHistory: Array<{
        id: number;
        amount: number;
        reason: string;
        created_at: string | null;
    }>;
    notifications: Array<{
        id: string;
        title: string;
        message: string;
        read_at: string | null;
        created_at: string;
    }>;
}>();

const number = (value: number): string => new Intl.NumberFormat().format(value);

const timeAgo = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString(undefined, {
              month: 'short',
              day: 'numeric',
          })
        : '—';
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Player command centre"
            :title="`Welcome back, ${$page.props.auth.user.username}`"
            subtitle="Track your progress, jump into a match and keep your streak alive."
        >
            <template #actions>
                <Link
                    href="/games"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-4 py-2 text-sm font-bold text-[#06101a] transition hover:brightness-110"
                >
                    <Gamepad2 class="size-4" /> Quick play
                </Link>
                <Link
                    href="/rooms"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40 hover:text-cyan-200"
                >
                    <Users class="size-4" /> Find a room
                </Link>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                label="Points"
                :value="number(props.stats.points)"
                :icon="Coins"
                accent="amber"
                hint="Spendable balance"
            />
            <StatTile
                label="Win rate"
                :value="`${props.stats.win_rate}%`"
                :icon="TrendingUp"
                accent="emerald"
                :hint="`${props.stats.wins} wins / ${props.stats.games_played} games`"
            />
            <StatTile
                label="Current streak"
                :value="props.stats.current_streak"
                :icon="Flame"
                accent="rose"
                :hint="`Best streak: ${props.stats.best_streak}`"
            />
            <StatTile
                label="Global rank"
                :value="props.stats.rank ? `#${props.stats.rank}` : '—'"
                :icon="Trophy"
                accent="cyan"
                hint="All-time leaderboard"
            />
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
            <div class="space-y-6">
                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-white">
                                Jump back in
                            </h2>
                            <p class="text-xs text-slate-500">
                                Solo against adaptive AI or multiplayer in a
                                room.
                            </p>
                        </div>
                        <Link
                            href="/games"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            All games
                        </Link>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <Link
                            v-for="game in props.games"
                            :key="game.slug"
                            :href="`/games/${game.slug}`"
                            class="group rounded-xl border border-white/10 bg-white/[0.03] p-4 transition hover:border-cyan-400/30 hover:bg-white/[0.06]"
                        >
                            <div
                                class="flex size-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-cyan-300"
                            >
                                <GameIcon :name="game.icon" class="size-5" />
                            </div>
                            <p class="mt-3 text-sm font-semibold text-white">
                                {{ game.name }}
                            </p>
                            <p
                                class="mt-0.5 line-clamp-2 text-xs text-slate-500"
                            >
                                {{ game.tagline }}
                            </p>
                        </Link>
                    </div>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">Recent matches</h2>
                        <Link
                            href="/leaderboard"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            Leaderboard
                        </Link>
                    </div>

                    <div v-if="props.recentGames.length" class="mt-4 space-y-2">
                        <div
                            v-for="result in props.recentGames"
                            :key="result.id"
                            class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] px-3 py-2.5"
                        >
                            <div
                                class="flex size-9 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-slate-300"
                            >
                                <GameIcon
                                    :name="result.game?.icon"
                                    class="size-4"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-white"
                                >
                                    {{ result.game?.name }}
                                    <span
                                        v-if="result.opponent"
                                        class="text-slate-500"
                                    >
                                        vs {{ result.opponent }}
                                    </span>
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ timeAgo(result.created_at) }}
                                    <span v-if="result.is_multiplayer">
                                        · Multiplayer</span
                                    >
                                </p>
                            </div>
                            <ResultBadge :result="result.result" />
                            <span
                                class="w-14 text-right text-sm font-semibold"
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
                    <EmptyState
                        v-else
                        class="mt-4"
                        title="No matches yet"
                        description="Start your first game and your history will show up here."
                    >
                        <Link
                            href="/games"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-cyan-400 px-3 py-1.5 text-xs font-bold text-[#06101a]"
                        >
                            Play now <ArrowRight class="size-3.5" />
                        </Link>
                    </EmptyState>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">
                            Points activity
                        </h2>
                        <Link
                            href="/rewards"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            Rewards store
                        </Link>
                    </div>
                    <ul
                        v-if="props.pointsHistory.length"
                        class="mt-3 divide-y divide-white/5"
                    >
                        <li
                            v-for="entry in props.pointsHistory"
                            :key="entry.id"
                            class="flex items-center justify-between gap-3 py-2.5 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-slate-300">
                                    {{ entry.reason }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ timeAgo(entry.created_at) }}
                                </p>
                            </div>
                            <span
                                class="font-semibold"
                                :class="
                                    entry.amount >= 0
                                        ? 'text-emerald-300'
                                        : 'text-rose-300'
                                "
                            >
                                {{ entry.amount >= 0 ? '+' : ''
                                }}{{ entry.amount }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-slate-500">
                        Points you earn or spend will appear here.
                    </p>
                </section>
            </div>

            <div class="space-y-6">
                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">Active rooms</h2>
                        <Link
                            href="/rooms"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            Browse
                        </Link>
                    </div>
                    <div v-if="props.activeRooms.length" class="mt-3 space-y-2">
                        <Link
                            v-for="room in props.activeRooms"
                            :key="room.code"
                            :href="`/rooms/${room.code}`"
                            class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5 transition hover:border-cyan-400/30"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-medium text-white"
                                >
                                    {{ room.name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ room.game.name }} ·
                                    {{ room.players_count }}/{{
                                        room.max_players
                                    }}
                                </p>
                            </div>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                :class="
                                    room.status === 'in_progress'
                                        ? 'bg-violet-400/15 text-violet-300'
                                        : 'bg-emerald-400/15 text-emerald-300'
                                "
                            >
                                {{ room.status.replace('_', ' ') }}
                            </span>
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-500">
                        You are not in any rooms right now.
                    </p>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">Top players</h2>
                        <Trophy class="size-4 text-amber-300" />
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
                                {{ number(player.points) }}
                            </span>
                        </li>
                    </ol>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">Achievements</h2>
                        <Link
                            href="/achievements"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            View all
                        </Link>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ props.achievements.unlocked }} of
                        {{ props.achievements.total }} unlocked
                    </p>
                    <div class="mt-3 space-y-2">
                        <div
                            v-for="achievement in props.achievements.latest"
                            :key="achievement.name"
                            class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5"
                        >
                            <div
                                class="flex size-9 items-center justify-center rounded-lg border border-amber-400/20 bg-amber-400/10 text-amber-300"
                            >
                                <GameIcon
                                    :name="achievement.icon"
                                    class="size-4"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-white"
                                >
                                    {{ achievement.name }}
                                </p>
                                <p class="text-xs text-slate-500 capitalize">
                                    {{ achievement.tier }} tier
                                </p>
                            </div>
                            <Medal class="size-4 text-amber-300" />
                        </div>
                        <p
                            v-if="props.achievements.latest.length === 0"
                            class="text-sm text-slate-500"
                        >
                            Win your first match to unlock a badge.
                        </p>
                    </div>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center gap-2">
                        <Bell class="size-4 text-violet-300" />
                        <h2 class="font-semibold text-white">Notifications</h2>
                    </div>
                    <ul
                        v-if="props.notifications.length"
                        class="mt-3 space-y-3"
                    >
                        <li
                            v-for="notification in props.notifications"
                            :key="notification.id"
                            class="rounded-xl border border-white/5 bg-white/[0.02] px-3 py-2.5"
                        >
                            <p class="text-sm font-medium text-white">
                                {{ notification.title }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ notification.message }}
                            </p>
                            <p class="mt-1 text-[10px] text-slate-600">
                                {{ notification.created_at }}
                            </p>
                        </li>
                    </ul>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        No notifications yet.
                    </p>
                </section>

                <Link
                    href="/rewards"
                    class="panel panel-hover flex items-center gap-3 p-5"
                >
                    <div
                        class="flex size-10 items-center justify-center rounded-xl border border-violet-400/20 bg-violet-400/10 text-violet-300"
                    >
                        <Gift class="size-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white">
                            Rewards store
                        </p>
                        <p class="text-xs text-slate-500">
                            Spend {{ number(props.stats.points) }} points on
                            titles and badges.
                        </p>
                    </div>
                    <ArrowRight class="size-4 text-slate-400" />
                </Link>
            </div>
        </div>
    </div>
</template>
