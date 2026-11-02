<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ChartColumn,
    Coins,
    Gamepad2,
    LayoutDashboard,
    Radar,
    ShieldCheck,
    TriangleAlert,
    Trophy,
    UserCog,
    Users,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import ResultBadge from '@/components/ResultBadge.vue';
import StatTile from '@/components/StatTile.vue';
import type { LeaderboardEntry } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin', href: '/admin' }],
    },
});

type ActivityDay = {
    date: string;
    label: string;
    registrations: number;
    matches: number;
};

const props = defineProps<{
    counts: {
        users: number;
        players: number;
        suspended: number;
        games_played: number;
        active_rooms: number;
        waiting_rooms: number;
        sessions_today: number;
        points_awarded: number;
        total_points: number;
        games: number;
    };
    recentResults: Array<{
        id: number;
        result: string;
        points_awarded: number;
        is_multiplayer: boolean;
        created_at: string | null;
        user: string;
        game: string;
    }>;
    suspicious: Array<{
        id: number;
        type: string;
        severity: string;
        description: string;
        user: string | null;
        created_at: string | null;
    }>;
    topPlayers: LeaderboardEntry[];
    activity: ActivityDay[];
    activeRooms: Array<{
        code: string;
        name: string;
        status: string;
        game: string;
        host: string;
        players_count: number;
        max_players: number;
    }>;
}>();

const maxActivity = (): number =>
    Math.max(
        1,
        ...props.activity.map((day) =>
            Math.max(day.registrations, day.matches),
        ),
    );

const barHeight = (value: number): string =>
    `${Math.round((value / maxActivity()) * 100)}%`;

const severityTone: Record<string, string> = {
    high: 'border-rose-400/30 bg-rose-400/10 text-rose-300',
    medium: 'border-amber-400/30 bg-amber-400/10 text-amber-300',
    low: 'border-cyan-400/30 bg-cyan-400/10 text-cyan-300',
};

const number = (value: number): string => new Intl.NumberFormat().format(value);
</script>

<template>
    <Head title="Admin overview" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Platform overview"
            subtitle="Live counts, recent activity and anything that needs a closer look."
        >
            <template #actions>
                <Link
                    href="/admin/users"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40"
                >
                    <UserCog class="size-4" /> Manage users
                </Link>
                <Link
                    href="/admin/activity"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-4 py-2 text-sm font-bold text-[#06101a]"
                >
                    <Radar class="size-4" /> Activity log
                </Link>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                label="Total users"
                :value="number(props.counts.users)"
                :icon="Users"
                accent="cyan"
                :hint="`${props.counts.players} players · ${props.counts.suspended} suspended`"
            />
            <StatTile
                label="Matches played"
                :value="number(props.counts.games_played)"
                :icon="Gamepad2"
                accent="violet"
                :hint="`${props.counts.sessions_today} sessions today`"
            />
            <StatTile
                label="Active rooms"
                :value="props.counts.active_rooms"
                :icon="LayoutDashboard"
                accent="emerald"
                :hint="`${props.counts.waiting_rooms} waiting`"
            />
            <StatTile
                label="Points in economy"
                :value="number(props.counts.total_points)"
                :icon="Coins"
                accent="amber"
                :hint="`${number(props.counts.points_awarded)} awarded`"
            />
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
            <div class="space-y-6">
                <section class="panel p-5">
                    <div class="flex items-center gap-2">
                        <ChartColumn class="size-4 text-cyan-300" />
                        <h2 class="font-semibold text-white">Last 7 days</h2>
                    </div>
                    <div class="mt-5 flex items-end gap-3">
                        <div
                            v-for="day in props.activity"
                            :key="day.date"
                            class="flex flex-1 flex-col items-center gap-2"
                        >
                            <div
                                class="flex h-32 w-full items-end justify-center gap-1"
                            >
                                <div
                                    class="w-1/3 rounded-t-md bg-cyan-400/70 transition-all"
                                    :style="{
                                        height: barHeight(day.registrations),
                                    }"
                                    :title="`${day.registrations} registrations`"
                                />
                                <div
                                    class="w-1/3 rounded-t-md bg-violet-400/70 transition-all"
                                    :style="{ height: barHeight(day.matches) }"
                                    :title="`${day.matches} matches`"
                                />
                            </div>
                            <span class="text-[10px] text-slate-500">{{
                                day.label
                            }}</span>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex items-center gap-4 text-xs text-slate-400"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-cyan-400" />
                            Registrations
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-violet-400" />
                            Matches
                        </span>
                    </div>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-white">Recent results</h2>
                        <Link
                            href="/admin/activity"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            View all
                        </Link>
                    </div>
                    <div
                        v-if="props.recentResults.length"
                        class="mt-3 divide-y divide-white/5"
                    >
                        <div
                            v-for="result in props.recentResults"
                            :key="result.id"
                            class="flex items-center gap-3 py-2.5"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm text-white">
                                    <span class="font-medium">{{
                                        result.user
                                    }}</span>
                                    <span class="text-slate-500">
                                        · {{ result.game }}</span
                                    >
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ result.created_at }}
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
                    <p v-else class="mt-3 text-sm text-slate-500">
                        No results yet.
                    </p>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center gap-2">
                        <Activity class="size-4 text-violet-300" />
                        <h2 class="font-semibold text-white">Live rooms</h2>
                    </div>
                    <div
                        v-if="props.activeRooms.length"
                        class="mt-3 grid gap-2 sm:grid-cols-2"
                    >
                        <Link
                            v-for="room in props.activeRooms"
                            :key="room.code"
                            :href="`/admin/rooms?status=${room.status}`"
                            class="rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5 transition hover:border-cyan-400/30"
                        >
                            <p class="truncate text-sm font-medium text-white">
                                {{ room.name }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ room.game }} · {{ room.players_count }}/{{
                                    room.max_players
                                }}
                                · {{ room.host }}
                            </p>
                        </Link>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        No rooms open.
                    </p>
                </section>
            </div>

            <div class="space-y-6">
                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2
                            class="flex items-center gap-2 font-semibold text-white"
                        >
                            <TriangleAlert class="size-4 text-amber-300" />
                            Suspicious activity
                        </h2>
                        <span class="text-xs text-slate-500">
                            {{ props.suspicious.length }}
                        </span>
                    </div>
                    <div v-if="props.suspicious.length" class="mt-3 space-y-2">
                        <div
                            v-for="event in props.suspicious"
                            :key="event.id"
                            class="rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p
                                    class="truncate text-sm font-medium text-white"
                                >
                                    {{ event.type }}
                                </p>
                                <span
                                    class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase"
                                    :class="
                                        severityTone[event.severity] ??
                                        severityTone.low
                                    "
                                >
                                    {{ event.severity }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ event.description }}
                            </p>
                            <p class="mt-1 text-[10px] text-slate-600">
                                {{ event.user ?? 'System' }} ·
                                {{ event.created_at }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        Nothing flagged. All clear.
                    </p>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2
                            class="flex items-center gap-2 font-semibold text-white"
                        >
                            <Trophy class="size-4 text-amber-300" /> Top players
                        </h2>
                        <Link
                            href="/leaderboard"
                            class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                        >
                            Leaderboard
                        </Link>
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
                            <span
                                class="min-w-0 flex-1 truncate text-sm text-slate-300"
                            >
                                {{ player.username }}
                            </span>
                            <span class="text-xs font-bold text-cyan-300">
                                {{ number(player.points) }}
                            </span>
                        </li>
                    </ol>
                    <EmptyState
                        v-if="props.topPlayers.length === 0"
                        class="mt-3"
                        title="No ranked players"
                        description="Results will populate the leaderboard."
                    />
                </section>

                <section class="panel p-5">
                    <h2
                        class="flex items-center gap-2 font-semibold text-white"
                    >
                        <ShieldCheck class="size-4 text-emerald-300" /> Platform
                        health
                    </h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Games configured</dt>
                            <dd class="text-white">{{ props.counts.games }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Suspended accounts</dt>
                            <dd class="text-rose-300">
                                {{ props.counts.suspended }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Waiting rooms</dt>
                            <dd class="text-emerald-300">
                                {{ props.counts.waiting_rooms }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Sessions today</dt>
                            <dd class="text-cyan-300">
                                {{ props.counts.sessions_today }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>
</template>
