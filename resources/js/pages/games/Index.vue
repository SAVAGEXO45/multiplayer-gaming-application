<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Coins, Gamepad2, Target, Trophy, Users } from '@lucide/vue';
import GameCard from '@/components/GameCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatTile from '@/components/StatTile.vue';
import type { Game } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Games', href: '/games' }],
    },
});

const props = defineProps<{
    games: Game[];
    summary: {
        games_played: number;
        wins: number;
        points: number;
        win_rate: number;
    };
}>();
</script>

<template>
    <Head title="Games" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="The arcade floor"
            title="Choose your challenge"
            subtitle="Every game has three difficulty levels, full rules and server-verified scoring."
        >
            <template #actions>
                <Link
                    href="/rooms"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40 hover:text-cyan-200"
                >
                    <Users class="size-4" /> Multiplayer rooms
                </Link>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                label="Games played"
                :value="props.summary.games_played"
                :icon="Gamepad2"
                accent="cyan"
            />
            <StatTile
                label="Total wins"
                :value="props.summary.wins"
                :icon="Trophy"
                accent="emerald"
            />
            <StatTile
                label="Win rate"
                :value="`${props.summary.win_rate}%`"
                :icon="Target"
                accent="violet"
            />
            <StatTile
                label="Points balance"
                :value="props.summary.points"
                :icon="Coins"
                accent="amber"
            />
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <GameCard
                v-for="game in props.games"
                :key="game.slug"
                :game="game"
            />
        </section>

        <section class="panel p-6">
            <h2 class="font-semibold text-white">How scoring works</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div
                    class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                >
                    <p class="text-sm font-semibold text-cyan-300">Solo</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Beat the AI to earn the full win reward, scaled by
                        difficulty. Draws and losses still award participation
                        points.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                >
                    <p class="text-sm font-semibold text-violet-300">
                        Multiplayer
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Winners take the win reward, draws split the draw reward
                        and a loss deducts points — never below zero.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                >
                    <p class="text-sm font-semibold text-amber-300">
                        Fair play
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Moves are validated on the server. Client-submitted
                        scores, winners and points are rejected outright.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
