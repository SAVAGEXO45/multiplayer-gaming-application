<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Lock, Plus, Users } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Rooms', href: '/rooms' },
            { title: 'Create', href: '/rooms/create' },
        ],
    },
});

const props = defineProps<{
    games: Array<{
        id: number;
        slug: string;
        name: string;
        tagline: string | null;
        icon: string;
        accent: string;
        min_players: number;
        max_players: number;
    }>;
}>();

const page = usePage();
const queryGame = computed(() => {
    const url = new URL(page.url, 'http://localhost');
    const slug = url.searchParams.get('game');

    return props.games.find((game) => game.slug === slug) ?? props.games[0];
});

const selectedGameId = ref<number | null>(queryGame.value?.id ?? null);

const selectedGame = computed(
    () => props.games.find((game) => game.id === selectedGameId.value) ?? null,
);

const minPlayers = computed(() =>
    Math.max(2, selectedGame.value?.min_players ?? 2),
);
const maxPlayers = computed(() =>
    Math.max(minPlayers.value, selectedGame.value?.max_players ?? 2),
);

const form = useForm({
    game_id: selectedGameId.value,
    name: '',
    max_players: maxPlayers.value,
    is_private: false,
    password: '',
});

watch(selectedGame, (game) => {
    if (!game) {
        return;
    }

    form.game_id = game.id;
    form.max_players = Math.max(2, game.max_players);
});

watch(
    () => form.is_private,
    (isPrivate) => {
        if (!isPrivate) {
            form.password = '';
        }
    },
);

const submit = (): void => {
    form.post('/rooms');
};
</script>

<template>
    <Head title="Create room" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="New lobby"
            title="Create a room"
            subtitle="Pick a game, set the capacity and share the room code with your rival."
        >
            <template #actions>
                <Link
                    href="/rooms"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/40 hover:text-cyan-200"
                >
                    <ArrowLeft class="size-4" /> Back to rooms
                </Link>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <form class="panel space-y-6 p-6" @submit.prevent="submit">
                <div class="space-y-2">
                    <label class="field-label">Game</label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="game in props.games"
                            :key="game.id"
                            type="button"
                            class="flex items-start gap-3 rounded-xl border p-4 text-left transition"
                            :class="
                                selectedGameId === game.id
                                    ? 'border-cyan-400/50 bg-cyan-400/10'
                                    : 'border-white/10 bg-white/[0.03] hover:border-white/25'
                            "
                            @click="selectedGameId = game.id"
                        >
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-cyan-300"
                            >
                                <GameIcon :name="game.icon" class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white">
                                    {{ game.name }}
                                </p>
                                <p class="line-clamp-2 text-xs text-slate-500">
                                    {{ game.tagline }}
                                </p>
                                <p class="mt-1 text-[10px] text-slate-600">
                                    Up to {{ game.max_players }} players
                                </p>
                            </div>
                        </button>
                    </div>
                    <p v-if="form.errors.game_id" class="text-xs text-rose-300">
                        {{ form.errors.game_id }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <label for="name" class="field-label">Room name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        minlength="3"
                        maxlength="60"
                        placeholder="Friday night showdown"
                        class="h-10 w-full rounded-xl border border-white/10 bg-white/5 px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                    />
                    <p v-if="form.errors.name" class="text-xs text-rose-300">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <label for="max_players" class="field-label">
                        Capacity ({{ minPlayers }}–{{ maxPlayers }})
                    </label>
                    <select
                        id="max_players"
                        v-model.number="form.max_players"
                        class="h-10 w-full rounded-xl border border-white/10 bg-[#0b1020] px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                    >
                        <option
                            v-for="count in maxPlayers - minPlayers + 1"
                            :key="count"
                            :value="minPlayers + count - 1"
                        >
                            {{ minPlayers + count - 1 }} players
                        </option>
                    </select>
                    <p class="text-xs text-slate-500">
                        The room must be full and every player ready before the
                        host can start.
                    </p>
                    <p
                        v-if="form.errors.max_players"
                        class="text-xs text-rose-300"
                    >
                        {{ form.errors.max_players }}
                    </p>
                </div>

                <div
                    class="space-y-3 rounded-xl border border-white/10 bg-white/[0.03] p-4"
                >
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            v-model="form.is_private"
                            type="checkbox"
                            class="size-4 rounded border-white/20 bg-white/10 accent-cyan-400"
                        />
                        <span
                            class="flex items-center gap-1.5 text-sm font-medium text-white"
                        >
                            <Lock class="size-3.5 text-amber-300" /> Private
                            room
                        </span>
                    </label>
                    <p class="text-xs text-slate-500">
                        Private rooms require a password before players can
                        join.
                    </p>

                    <div v-if="form.is_private" class="grid gap-2">
                        <label for="password" class="field-label"
                            >Room password</label
                        >
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            minlength="4"
                            maxlength="50"
                            required
                            placeholder="At least 4 characters"
                            class="h-10 w-full rounded-xl border border-white/10 bg-white/5 px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-rose-300"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-5 py-2.5 text-sm font-bold text-[#06101a] transition hover:brightness-110 disabled:opacity-60"
                    :disabled="form.processing || !selectedGame"
                >
                    <Plus class="size-4" /> Create room
                </button>
            </form>

            <aside class="space-y-4">
                <div class="panel p-5">
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-white"
                    >
                        <Users class="size-4 text-cyan-300" /> Lobby checklist
                    </h2>
                    <ul class="mt-3 space-y-2 text-xs text-slate-400">
                        <li class="flex gap-2">
                            <span class="text-cyan-300">1.</span> Create the
                            room and share the 6-character code.
                        </li>
                        <li class="flex gap-2">
                            <span class="text-cyan-300">2.</span> Wait until the
                            room is full and everyone has pressed Ready.
                        </li>
                        <li class="flex gap-2">
                            <span class="text-cyan-300">3.</span> The host
                            starts the match and turns are enforced by the
                            server.
                        </li>
                        <li class="flex gap-2">
                            <span class="text-cyan-300">4.</span> Leaving
                            mid-match counts as a forfeit.
                        </li>
                    </ul>
                </div>

                <div v-if="selectedGame" class="panel p-5">
                    <h2 class="text-sm font-semibold text-white">
                        Selected game
                    </h2>
                    <div class="mt-3 flex items-center gap-3">
                        <div
                            class="flex size-11 items-center justify-center rounded-xl border border-cyan-400/20 bg-cyan-400/10 text-cyan-300"
                        >
                            <GameIcon
                                :name="selectedGame.icon"
                                class="size-5"
                            />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">
                                {{ selectedGame.name }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ selectedGame.min_players }}–{{
                                    selectedGame.max_players
                                }}
                                players
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">
                        {{ selectedGame.tagline }}
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>
