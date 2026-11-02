<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Calendar,
    Coins,
    Flame,
    Gamepad2,
    Medal,
    Settings,
    Target,
    Trophy,
    Users,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import ResultBadge from '@/components/ResultBadge.vue';
import StatTile from '@/components/StatTile.vue';
import type { GameResultItem } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Players', href: '/leaderboard' }],
    },
});

const props = defineProps<{
    player: {
        id: number;
        name: string;
        username: string;
        avatar_url: string;
        bio: string | null;
        role: string;
        created_at: string | null;
    };
    stats: {
        games_played: number;
        wins: number;
        losses: number;
        draws: number;
        points: number;
        current_streak: number;
        best_streak: number;
        multiplayer_wins: number;
        win_rate: number;
    };
    perGame: Array<{
        slug: string;
        name: string;
        games_played: number;
        wins: number;
        losses: number;
        draws: number;
        points: number;
        win_rate: number;
    }>;
    achievements: Array<{
        slug: string;
        name: string;
        description: string;
        icon: string;
        tier: string;
        points_reward: number;
        unlocked_at: string | null;
    }>;
    achievementProgress: {
        unlocked: number;
        total: number;
    };
    recentGames: GameResultItem[];
    rank: number | null;
    isSelf: boolean;
}>();

const joined = props.player.created_at
    ? new Date(props.player.created_at).toLocaleDateString(undefined, {
          month: 'long',
          year: 'numeric',
      })
    : 'Unknown';

const number = (value: number): string => new Intl.NumberFormat().format(value);

const tierTone: Record<string, string> = {
    bronze: 'border-orange-400/25 bg-orange-400/10 text-orange-300',
    silver: 'border-slate-300/25 bg-slate-300/10 text-slate-200',
    gold: 'border-amber-300/30 bg-amber-300/10 text-amber-300',
};
</script>

<template>
    <Head :title="props.player.username" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Player profile"
            :title="props.player.username"
            :subtitle="props.player.bio ?? 'No bio yet.'"
        >
            <template #actions>
                <Link
                    v-if="props.isSelf"
                    href="/settings/profile"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40"
                >
                    <Settings class="size-4" /> Edit profile
                </Link>
                <Link
                    href="/leaderboard"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40"
                >
                    <Trophy class="size-4" /> Leaderboard
                </Link>
            </template>
        </PageHeader>

        <section class="panel relative overflow-hidden p-6">
            <div
                class="pointer-events-none absolute -top-24 -right-10 size-72 rounded-full bg-cyan-400/10 blur-3xl"
            />
            <div
                class="relative flex flex-col gap-5 sm:flex-row sm:items-center"
            >
                <PlayerAvatar
                    :src="props.player.avatar_url"
                    :name="props.player.name"
                    size="xl"
                />
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-2xl font-bold text-white">
                            {{ props.player.name }}
                        </h2>
                        <span
                            v-if="props.player.role === 'admin'"
                            class="rounded-full border border-violet-400/30 bg-violet-400/10 px-2 py-0.5 text-[10px] font-semibold text-violet-300 uppercase"
                        >
                            Admin
                        </span>
                        <span
                            v-if="props.isSelf"
                            class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 py-0.5 text-[10px] font-semibold text-cyan-300 uppercase"
                        >
                            You
                        </span>
                    </div>
                    <p class="mt-0.5 text-sm text-slate-400">
                        @{{ props.player.username }}
                    </p>
                    <div
                        class="mt-3 flex flex-wrap items-center gap-4 text-xs text-slate-500"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Calendar class="size-3.5" /> Joined {{ joined }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <Trophy class="size-3.5 text-amber-300" />
                            {{
                                props.rank
                                    ? `Global rank #${props.rank}`
                                    : 'Unranked'
                            }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <Medal class="size-3.5 text-violet-300" />
                            {{ props.achievementProgress.unlocked }}/{{
                                props.achievementProgress.total
                            }}
                            achievements
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-black text-cyan-300">
                        {{ number(props.stats.points) }}
                    </p>
                    <p
                        class="text-[10px] tracking-widest text-slate-500 uppercase"
                    >
                        Points
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                label="Games played"
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
                label="Best streak"
                :value="props.stats.best_streak"
                :icon="Flame"
                accent="rose"
                :hint="`Current: ${props.stats.current_streak}`"
            />
            <StatTile
                label="Multiplayer wins"
                :value="props.stats.multiplayer_wins"
                :icon="Users"
                accent="violet"
            />
        </section>

        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
            <div class="space-y-6">
                <section class="panel p-5">
                    <h2
                        class="flex items-center gap-2 font-semibold text-white"
                    >
                        <Target class="size-4 text-cyan-300" /> Per-game record
                    </h2>
                    <div v-if="props.perGame.length" class="mt-4 space-y-3">
                        <div
                            v-for="game in props.perGame"
                            :key="game.slug"
                            class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <Link
                                    :href="`/games/${game.slug}`"
                                    class="text-sm font-semibold text-white hover:text-cyan-200"
                                >
                                    {{ game.name }}
                                </Link>
                                <span class="text-xs text-slate-500">
                                    {{ game.games_played }} games
                                </span>
                            </div>
                            <div class="mt-2">
                                <ProgressBar
                                    :value="game.wins"
                                    :max="Math.max(1, game.games_played)"
                                    accent="cyan"
                                />
                            </div>
                            <div
                                class="mt-2 flex flex-wrap gap-4 text-xs text-slate-400"
                            >
                                <span class="text-emerald-300"
                                    >{{ game.wins }}W</span
                                >
                                <span class="text-rose-300"
                                    >{{ game.losses }}L</span
                                >
                                <span class="text-amber-300"
                                    >{{ game.draws }}D</span
                                >
                                <span>{{ game.win_rate }}% win rate</span>
                                <span class="ml-auto text-cyan-300">
                                    {{ game.points }} pts
                                </span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-500">
                        No games recorded yet.
                    </p>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">Recent matches</h2>
                    </div>
                    <div v-if="props.recentGames.length" class="mt-3 space-y-2">
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
                                    {{
                                        result.is_multiplayer
                                            ? 'Multiplayer'
                                            : 'Solo'
                                    }}
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
                        description="This player has not finished a match."
                    />
                </section>
            </div>

            <aside class="space-y-6">
                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">Achievements</h2>
                        <Link
                            v-if="props.isSelf"
                            href="/achievements"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            View all
                        </Link>
                    </div>
                    <div
                        v-if="props.achievements.length"
                        class="mt-3 space-y-2"
                    >
                        <div
                            v-for="achievement in props.achievements"
                            :key="achievement.slug"
                            class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5"
                        >
                            <div
                                class="flex size-9 items-center justify-center rounded-lg border"
                                :class="
                                    tierTone[achievement.tier] ??
                                    tierTone.bronze
                                "
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
                                <p class="truncate text-xs text-slate-500">
                                    {{ achievement.description }}
                                </p>
                            </div>
                            <span class="text-xs font-semibold text-amber-300">
                                +{{ achievement.points_reward }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        No achievements unlocked yet.
                    </p>
                </section>

                <section class="panel p-5">
                    <h2 class="font-semibold text-white">Stats snapshot</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Losses</dt>
                            <dd class="text-rose-300">
                                {{ props.stats.losses }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Draws</dt>
                            <dd class="text-amber-300">
                                {{ props.stats.draws }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Win rate</dt>
                            <dd class="text-emerald-300">
                                {{ props.stats.win_rate }}%
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Points</dt>
                            <dd class="text-cyan-300">
                                {{ number(props.stats.points) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <Link
                    href="/games"
                    class="panel panel-hover flex items-center gap-3 p-4"
                >
                    <Coins class="size-4 text-amber-300" />
                    <span class="text-sm font-medium text-slate-300">
                        Challenge the arcade
                    </span>
                </Link>
            </aside>
        </div>
    </div>
</template>
