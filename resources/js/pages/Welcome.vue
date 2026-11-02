<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Coins,
    Gamepad2,
    Gift,
    Medal,
    ShieldCheck,
    Sparkles,
    Swords,
    Trophy,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import GameCard from '@/components/GameCard.vue';
import GameIcon from '@/components/GameIcon.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import type { Game, LeaderboardEntry } from '@/types/game';

const props = defineProps<{
    games: Game[];
    topPlayers: LeaderboardEntry[];
    stats: {
        players: number;
        matches: number;
        matches_today: number;
    };
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const features = [
    {
        icon: Swords,
        title: 'Three real games',
        description:
            'Tic-Tac-Toe, Rock Paper Scissors and Number Guess — each with difficulty levels and server-verified scoring.',
    },
    {
        icon: Users,
        title: 'Live multiplayer rooms',
        description:
            'Create a room, share the code, ready up and play turn-by-turn with automatic forfeits and validation.',
    },
    {
        icon: Trophy,
        title: 'Season leaderboards',
        description:
            'Climb global, weekly and monthly boards, or filter by game to prove you are the best at your favourite.',
    },
    {
        icon: Gift,
        title: 'Points & rewards',
        description:
            'Earn points for every match, unlock achievements and spend your balance on titles, badges and themes.',
    },
];

const number = (value: number): string => new Intl.NumberFormat().format(value);
</script>

<template>
    <Head title="Neon Arcade — Multiplayer Gaming Platform" />

    <div class="min-h-screen text-slate-100">
        <header
            class="sticky top-0 z-40 border-b border-white/5 bg-[#070a14]/70 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link href="/" class="flex items-center gap-2.5">
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-violet-500 text-[#06101a] shadow-[0_0_30px_-8px_rgba(34,211,238,0.8)]"
                    >
                        <Gamepad2 class="size-5" />
                    </span>
                    <span class="text-base font-bold tracking-tight text-white">
                        Neon<span class="text-cyan-300">Arcade</span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-6 text-sm md:flex">
                    <a
                        href="#games"
                        class="text-slate-400 transition hover:text-white"
                        >Games</a
                    >
                    <a
                        href="#features"
                        class="text-slate-400 transition hover:text-white"
                        >Features</a
                    >
                    <a
                        href="#leaderboard"
                        class="text-slate-400 transition hover:text-white"
                        >Leaderboard</a
                    >
                    <Link
                        v-if="user"
                        href="/leaderboard"
                        class="text-slate-400 transition hover:text-white"
                    >
                        Rankings
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <template v-if="user">
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-semibold text-[#06101a] transition hover:bg-cyan-300"
                        >
                            Dashboard <ArrowRight class="size-4" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="rounded-xl px-3 py-2 text-sm font-medium text-slate-300 transition hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            href="/register"
                            class="inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-semibold text-[#06101a] transition hover:bg-cyan-300"
                        >
                            Play free <ArrowRight class="size-4" />
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden">
                <div
                    class="pointer-events-none absolute -top-40 left-1/2 size-[42rem] -translate-x-1/2 rounded-full bg-cyan-500/10 blur-3xl"
                />
                <div
                    class="mx-auto grid w-full max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-24"
                >
                    <div class="relative space-y-7">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-cyan-400/25 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-200"
                        >
                            <Sparkles class="size-3.5" />
                            Server-authoritative scoring · No cheats
                        </span>

                        <h1
                            class="text-4xl leading-[1.05] font-black tracking-tight text-white sm:text-6xl"
                        >
                            Enter the arena.
                            <br />
                            <span class="text-gradient"
                                >Own the scoreboard.</span
                            >
                        </h1>

                        <p class="max-w-xl text-base text-slate-400 sm:text-lg">
                            A competitive multiplayer arcade with real rooms,
                            ranked leaderboards, achievements and rewards. Every
                            point is earned on the server — never trusted from
                            the browser.
                        </p>

                        <div class="flex flex-wrap items-center gap-3">
                            <Link
                                :href="user ? '/games' : '/register'"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-6 py-3 text-sm font-bold text-[#06101a] shadow-[0_18px_45px_-18px_rgba(34,211,238,0.9)] transition hover:brightness-110"
                            >
                                <Gamepad2 class="size-4" />
                                {{
                                    user
                                        ? 'Choose a game'
                                        : 'Create your account'
                                }}
                            </Link>
                            <Link
                                href="/leaderboard"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40 hover:text-cyan-200"
                            >
                                <Trophy class="size-4" /> View leaderboard
                            </Link>
                        </div>

                        <dl class="grid max-w-lg grid-cols-3 gap-3 pt-2">
                            <div class="panel p-4">
                                <dt
                                    class="text-xs tracking-wide text-slate-500 uppercase"
                                >
                                    Players
                                </dt>
                                <dd class="mt-1 text-xl font-bold text-white">
                                    {{ number(props.stats.players) }}
                                </dd>
                            </div>
                            <div class="panel p-4">
                                <dt
                                    class="text-xs tracking-wide text-slate-500 uppercase"
                                >
                                    Matches
                                </dt>
                                <dd class="mt-1 text-xl font-bold text-white">
                                    {{ number(props.stats.matches) }}
                                </dd>
                            </div>
                            <div class="panel p-4">
                                <dt
                                    class="text-xs tracking-wide text-slate-500 uppercase"
                                >
                                    Today
                                </dt>
                                <dd
                                    class="mt-1 text-xl font-bold text-cyan-300"
                                >
                                    {{ number(props.stats.matches_today) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="relative">
                        <div
                            class="pointer-events-none absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-cyan-400/15 via-transparent to-violet-500/15 blur-2xl"
                        />
                        <div class="panel relative overflow-hidden p-6">
                            <div
                                class="flex items-center justify-between border-b border-white/10 pb-4"
                            >
                                <div
                                    class="flex items-center gap-2 text-sm font-semibold text-white"
                                >
                                    <Trophy class="size-4 text-amber-300" />
                                    Top players
                                </div>
                                <Link
                                    href="/leaderboard"
                                    class="text-xs font-medium text-cyan-300 hover:text-cyan-200"
                                >
                                    All rankings
                                </Link>
                            </div>

                            <ol class="mt-3 divide-y divide-white/5">
                                <li
                                    v-for="player in props.topPlayers.slice(
                                        0,
                                        5,
                                    )"
                                    :key="player.user_id"
                                    class="flex items-center gap-3 py-3"
                                >
                                    <span
                                        class="w-5 text-center text-xs font-bold"
                                        :class="
                                            player.rank === 1
                                                ? 'text-amber-300'
                                                : player.rank === 2
                                                  ? 'text-slate-300'
                                                  : player.rank === 3
                                                    ? 'text-orange-300'
                                                    : 'text-slate-500'
                                        "
                                    >
                                        {{ player.rank }}
                                    </span>
                                    <PlayerAvatar
                                        :src="player.avatar_url"
                                        :name="player.name"
                                        size="sm"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-medium text-white"
                                        >
                                            {{ player.username }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ player.wins }} wins ·
                                            {{ player.win_rate }}%
                                        </p>
                                    </div>
                                    <span
                                        class="text-sm font-bold text-cyan-300"
                                    >
                                        {{ number(player.points) }}
                                    </span>
                                </li>
                                <li
                                    v-if="props.topPlayers.length === 0"
                                    class="py-8 text-center text-sm text-slate-500"
                                >
                                    No matches yet — be the first champion.
                                </li>
                            </ol>

                            <div
                                class="mt-4 flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs text-slate-400"
                            >
                                <ShieldCheck
                                    class="size-4 shrink-0 text-emerald-300"
                                />
                                Points, results and streaks are calculated and
                                stored server-side.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="games"
                class="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6"
            >
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.2em] text-cyan-300/80 uppercase"
                        >
                            The line-up
                        </p>
                        <h2
                            class="mt-1 text-3xl font-bold tracking-tight text-white"
                        >
                            Pick your game
                        </h2>
                    </div>
                    <p class="max-w-md text-sm text-slate-400">
                        Jump into a solo challenge against adaptive AI, or open
                        a room and invite a rival.
                    </p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <GameCard
                        v-for="game in props.games"
                        :key="game.slug"
                        :game="game"
                    />
                </div>
            </section>

            <section
                id="features"
                class="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="panel panel-hover flex gap-4 p-6"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-cyan-400/20 bg-cyan-400/10 text-cyan-300"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-white">
                                {{ feature.title }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="leaderboard"
                class="mx-auto w-full max-w-7xl px-4 pb-20 sm:px-6"
            >
                <div class="panel relative overflow-hidden p-8 sm:p-12">
                    <div
                        class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-violet-500/20 blur-3xl"
                    />
                    <div
                        class="relative flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="max-w-2xl space-y-3">
                            <span class="stat-chip">
                                <Medal class="size-3.5 text-amber-300" />
                                Seasons reset monthly
                            </span>
                            <h2
                                class="text-3xl font-bold tracking-tight text-white"
                            >
                                Your name belongs on the board.
                            </h2>
                            <p class="text-sm text-slate-400">
                                Every win, draw and loss is recorded. Earn
                                points, build streaks, unlock badges and climb
                                from rookie to legend.
                            </p>
                            <div
                                class="flex flex-wrap gap-4 pt-1 text-xs text-slate-400"
                            >
                                <span class="inline-flex items-center gap-1.5">
                                    <Coins class="size-4 text-amber-300" />
                                    Points for every match
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <BadgeCheck
                                        class="size-4 text-emerald-300"
                                    />
                                    15 achievements
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <GameIcon
                                        name="sparkles"
                                        class="size-4 text-violet-300"
                                    />
                                    Titles, badges & themes
                                </span>
                            </div>
                        </div>
                        <Link
                            :href="user ? '/dashboard' : '/register'"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-[#06101a] transition hover:bg-cyan-100"
                        >
                            {{ user ? 'Go to dashboard' : 'Start playing' }}
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/5">
            <div
                class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-3 px-4 py-8 text-xs text-slate-500 sm:flex-row sm:px-6"
            >
                <p>
                    © {{ new Date().getFullYear() }} Neon Arcade. Built for
                    competitive play.
                </p>
                <div class="flex items-center gap-4">
                    <Link
                        href="/leaderboard"
                        class="transition hover:text-slate-300"
                        >Leaderboard</Link
                    >
                    <Link href="/login" class="transition hover:text-slate-300"
                        >Log in</Link
                    >
                    <Link
                        href="/register"
                        class="transition hover:text-slate-300"
                        >Register</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
