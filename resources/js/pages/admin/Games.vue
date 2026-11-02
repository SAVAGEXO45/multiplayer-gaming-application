<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Coins,
    Gamepad2,
    Save,
    ToggleLeft,
    ToggleRight,
    Users,
} from '@lucide/vue';
import { reactive, ref } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Games', href: '/admin/games' },
        ],
    },
});

type AdminGame = {
    id: number;
    slug: string;
    name: string;
    tagline: string | null;
    icon: string;
    accent: string;
    is_active: boolean;
    supports_multiplayer: boolean;
    min_players: number;
    max_players: number;
    points_win: number;
    points_draw: number;
    points_loss: number;
    points_participation: number;
    results_count: number;
    sessions_count: number;
    rooms_count: number;
};

const props = defineProps<{
    games: AdminGame[];
}>();

type GameDraft = {
    is_active: boolean;
    points_win: number;
    points_draw: number;
    points_loss: number;
    points_participation: number;
    max_players: number;
};

const drafts = reactive<Record<number, GameDraft>>(
    Object.fromEntries(
        props.games.map((game) => [
            game.id,
            {
                is_active: game.is_active,
                points_win: game.points_win,
                points_draw: game.points_draw,
                points_loss: game.points_loss,
                points_participation: game.points_participation,
                max_players: game.max_players,
            },
        ]),
    ),
);

const saving = ref<number | null>(null);

const toggleActive = (game: AdminGame): void => {
    drafts[game.id].is_active = !drafts[game.id].is_active;
};

const save = (game: AdminGame): void => {
    saving.value = game.id;

    router.patch(`/admin/games/${game.id}`, drafts[game.id], {
        preserveScroll: true,
        onFinish: () => (saving.value = null),
    });
};
</script>

<template>
    <Head title="Admin — Games" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Games"
            subtitle="Toggle availability, tune the point economy and adjust room capacity."
        />

        <section class="grid gap-4 xl:grid-cols-2">
            <div
                v-for="game in props.games"
                :key="game.id"
                class="panel p-5"
                :class="drafts[game.id].is_active ? '' : 'opacity-80'"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-cyan-300"
                        >
                            <GameIcon :name="game.icon" class="size-6" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-white">
                                {{ game.name }}
                            </h2>
                            <p class="text-xs text-slate-500">
                                {{ game.tagline }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                        :class="
                            drafts[game.id].is_active
                                ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300'
                                : 'border-white/10 bg-white/5 text-slate-400'
                        "
                        @click="toggleActive(game)"
                    >
                        <ToggleRight
                            v-if="drafts[game.id].is_active"
                            class="size-4"
                        />
                        <ToggleLeft v-else class="size-4" />
                        {{ drafts[game.id].is_active ? 'Active' : 'Disabled' }}
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                    <div
                        class="rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2"
                    >
                        <p class="text-slate-500">Results</p>
                        <p class="mt-0.5 font-semibold text-white">
                            {{ game.results_count }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2"
                    >
                        <p class="text-slate-500">Sessions</p>
                        <p class="mt-0.5 font-semibold text-white">
                            {{ game.sessions_count }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2"
                    >
                        <p class="text-slate-500">Rooms</p>
                        <p class="mt-0.5 font-semibold text-white">
                            {{ game.rooms_count }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2"
                    >
                        <p class="text-slate-500">Players</p>
                        <p class="mt-0.5 font-semibold text-white">
                            {{ game.min_players }}–{{
                                drafts[game.id].max_players
                            }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <label class="grid gap-1">
                        <span class="field-label flex items-center gap-1">
                            <Coins class="size-3 text-emerald-300" /> Win
                        </span>
                        <input
                            v-model.number="drafts[game.id].points_win"
                            type="number"
                            min="0"
                            max="10000"
                            class="h-9 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                    </label>
                    <label class="grid gap-1">
                        <span class="field-label flex items-center gap-1">
                            <Coins class="size-3 text-amber-300" /> Draw
                        </span>
                        <input
                            v-model.number="drafts[game.id].points_draw"
                            type="number"
                            min="0"
                            max="10000"
                            class="h-9 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                    </label>
                    <label class="grid gap-1">
                        <span class="field-label flex items-center gap-1">
                            <Coins class="size-3 text-rose-300" /> Loss
                        </span>
                        <input
                            v-model.number="drafts[game.id].points_loss"
                            type="number"
                            min="-10000"
                            max="0"
                            class="h-9 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                    </label>
                    <label class="grid gap-1">
                        <span class="field-label flex items-center gap-1">
                            <Coins class="size-3 text-cyan-300" /> Play
                        </span>
                        <input
                            v-model.number="
                                drafts[game.id].points_participation
                            "
                            type="number"
                            min="0"
                            max="10000"
                            class="h-9 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                    </label>
                </div>

                <div
                    class="mt-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1">
                            <Gamepad2 class="size-3.5" /> {{ game.slug }}
                        </span>
                        <span
                            v-if="game.supports_multiplayer"
                            class="inline-flex items-center gap-1"
                        >
                            <Users class="size-3.5" /> Multiplayer
                        </span>
                        <label class="inline-flex items-center gap-1.5">
                            Max
                            <input
                                v-model.number="drafts[game.id].max_players"
                                type="number"
                                min="2"
                                max="8"
                                class="h-8 w-16 rounded-lg border border-white/10 bg-white/5 px-2 text-xs text-white outline-none focus:border-cyan-400/50"
                            />
                        </label>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-[#06101a] transition hover:bg-cyan-300 disabled:opacity-60"
                        :disabled="saving === game.id"
                        @click="save(game)"
                    >
                        <Save class="size-4" />
                        {{ saving === game.id ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </div>
        </section>

        <section
            class="panel flex items-start gap-3 p-4 text-xs text-slate-400"
        >
            <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-emerald-300" />
            <p>
                Score values are applied by the server when a match finishes.
                Disabled games are hidden from players but keep their history
                and statistics intact.
            </p>
        </section>
    </div>
</template>
