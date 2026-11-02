<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Coins, Users } from '@lucide/vue';
import { computed } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import type { Game } from '@/types/game';

const props = defineProps<{
    game: Game;
}>();

const accents: Record<string, { text: string; ring: string; glow: string }> = {
    cyan: {
        text: 'text-cyan-300',
        ring: 'border-cyan-400/30',
        glow: 'from-cyan-400/20',
    },
    violet: {
        text: 'text-violet-300',
        ring: 'border-violet-400/30',
        glow: 'from-violet-400/20',
    },
    amber: {
        text: 'text-amber-300',
        ring: 'border-amber-400/30',
        glow: 'from-amber-400/20',
    },
};

const accent = computed(() => accents[props.game.accent] ?? accents.cyan);
</script>

<template>
    <Link
        :href="`/games/${game.slug}`"
        class="panel panel-hover group relative flex flex-col overflow-hidden p-5"
    >
        <div
            class="pointer-events-none absolute -top-20 -right-16 size-44 rounded-full bg-gradient-to-br to-transparent opacity-60 blur-2xl transition group-hover:opacity-100"
            :class="accent.glow"
        />
        <div class="relative flex items-start justify-between gap-3">
            <div
                class="flex size-12 items-center justify-center rounded-2xl border bg-white/5"
                :class="[accent.ring, accent.text]"
            >
                <GameIcon :name="game.icon" class="size-6" />
            </div>
            <span
                v-if="game.supports_multiplayer"
                class="stat-chip"
                title="Supports multiplayer"
            >
                <Users class="size-3" /> Multiplayer
            </span>
            <span v-else class="stat-chip">Solo</span>
        </div>

        <div class="relative mt-4 flex-1">
            <h3 class="text-lg font-semibold text-white">{{ game.name }}</h3>
            <p class="mt-1 text-sm text-slate-400">
                {{ game.tagline ?? game.description }}
            </p>
        </div>

        <div class="relative mt-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 text-xs text-slate-400">
                <span class="inline-flex items-center gap-1">
                    <Coins class="size-3.5 text-amber-300" />
                    {{ game.points_win }} pts
                </span>
                <span v-if="game.stats" class="text-slate-500">
                    {{ game.stats.wins }}W / {{ game.stats.games_played }}G
                </span>
            </div>
            <span
                class="inline-flex items-center gap-1 text-xs font-semibold transition group-hover:gap-2"
                :class="accent.text"
            >
                Play <ArrowRight class="size-3.5" />
            </span>
        </div>
    </Link>
</template>
