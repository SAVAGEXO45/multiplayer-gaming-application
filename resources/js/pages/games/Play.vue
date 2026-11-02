<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bot,
    Check,
    Coins,
    Flag,
    Gamepad2,
    Info,
    LoaderCircle,
    Lock,
    RotateCcw,
    Send,
    Trophy,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import ResultBadge from '@/components/ResultBadge.vue';
import type { GameSession } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Play', href: '/games' }],
    },
});

const props = defineProps<{
    session: GameSession;
    game: {
        slug: string;
        name: string;
        icon: string;
        accent: string;
        instructions: string;
        points_win: number;
    };
    roomCode: string | null;
}>();

const page = usePage();
const myId = computed(() => Number(page.props.auth.user?.id ?? 0));
const state = computed(() => props.session.state);
const isFinished = computed(() => props.session.status !== 'active');
const isMultiplayer = computed(() => (props.session.players?.length ?? 0) > 1);

const myTurn = computed(
    () =>
        !isMultiplayer.value ||
        props.session.current_turn_user_id === null ||
        props.session.current_turn_user_id === myId.value,
);

const board = computed<(string | null)[]>(() => state.value.board ?? []);
const marks = computed<Record<string, string>>(() => state.value.marks ?? {});
const winningLine = computed<number[]>(() => state.value.winning_line ?? []);

const form = useForm<{
    position: number | null;
    choice: string;
    guess: number | null;
}>({
    position: null,
    choice: '',
    guess: null,
});

const gameError = computed(() => {
    const errors = form.errors as Record<string, string>;

    return (
        errors.position ?? errors.choice ?? errors.guess ?? errors.game ?? null
    );
});

const guessInput = ref<number | null>(null);

const submitPosition = (index: number): void => {
    if (isFinished.value || board.value[index] !== null || !myTurn.value) {
        return;
    }

    form.position = index;
    form.choice = '';
    form.guess = null;

    form.post(`/games/sessions/${props.session.uuid}/move`, {
        preserveScroll: true,
        onFinish: () => form.reset('position'),
    });
};

const submitChoice = (choice: string): void => {
    if (isFinished.value) {
        return;
    }

    form.choice = choice;
    form.position = null;
    form.guess = null;

    form.post(`/games/sessions/${props.session.uuid}/move`, {
        preserveScroll: true,
        onFinish: () => form.reset('choice'),
    });
};

const submitGuess = (): void => {
    if (isFinished.value || guessInput.value === null) {
        return;
    }

    form.guess = guessInput.value;
    form.position = null;
    form.choice = '';

    form.post(`/games/sessions/${props.session.uuid}/move`, {
        preserveScroll: true,
        onSuccess: () => (guessInput.value = null),
    });
};

const resign = (): void => {
    if (!window.confirm('Resign this match? This counts as a loss.')) {
        return;
    }

    router.post(`/games/sessions/${props.session.uuid}/resign`, undefined, {
        preserveScroll: true,
    });
};

const statusLabel = computed(() => {
    if (isFinished.value) {
        if (props.session.is_draw) {
            return 'Draw';
        }

        return props.session.winner_id === myId.value ? 'You won!' : 'You lost';
    }

    if (!myTurn.value) {
        return 'Waiting for opponent';
    }

    return 'Your turn';
});

const statusTone = computed(() => {
    if (isFinished.value) {
        return props.session.is_draw
            ? 'border-amber-400/30 bg-amber-400/10 text-amber-200'
            : props.session.winner_id === myId.value
              ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-200'
              : 'border-rose-400/30 bg-rose-400/10 text-rose-200';
    }

    return 'border-cyan-400/30 bg-cyan-400/10 text-cyan-200';
});

const rpsRounds = computed(
    () =>
        (state.value.history ?? []) as Array<{
            round: number;
            choices: Record<string, string>;
            ai_choice: string | null;
            winner_id: number | null;
        }>,
);

const myScore = computed(() => {
    const scores = state.value.scores ?? {};

    return Number(scores[String(myId.value)] ?? 0);
});

const opponentScore = computed(() => {
    if (!isMultiplayer.value) {
        return Number(state.value.ai_score ?? 0);
    }

    const opponent = props.session.players?.find((p) => p.id !== myId.value);

    if (!opponent) {
        return 0;
    }

    return Number((state.value.scores ?? {})[String(opponent.id)] ?? 0);
});

const myChoiceLocked = computed(
    () => state.value.choices?.[String(myId.value)] !== undefined,
);

const attempts = computed(
    () =>
        (state.value.attempts ?? []) as Array<{
            guess: number;
            feedback: string;
            remaining: number;
        }>,
);

const choiceEmoji: Record<string, string> = {
    rock: '✊',
    paper: '✋',
    scissors: '✌️',
};

const pollTimer = ref<number | null>(null);

onMounted(() => {
    if (
        props.session.status === 'active' &&
        (props.session.players?.length ?? 0) > 1
    ) {
        pollTimer.value = window.setInterval(() => {
            router.reload({ only: ['session'] });
        }, 4000);
    }
});

onUnmounted(() => {
    if (pollTimer.value !== null) {
        window.clearInterval(pollTimer.value);
    }
});
</script>

<template>
    <Head :title="`${props.game.name} — Play`" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <Link
                    :href="
                        props.roomCode
                            ? `/rooms/${props.roomCode}`
                            : `/games/${props.game.slug}`
                    "
                    class="flex size-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-300 transition hover:border-cyan-400/40 hover:text-cyan-200"
                >
                    <ArrowLeft class="size-4" />
                </Link>

                <div
                    class="flex size-11 items-center justify-center rounded-xl border border-cyan-400/25 bg-cyan-400/10 text-cyan-300"
                >
                    <GameIcon :name="props.game.icon" class="size-5" />
                </div>

                <div>
                    <h1 class="text-lg font-bold text-white">
                        {{ props.game.name }}
                    </h1>

                    <p class="text-xs text-slate-500">
                        <span v-if="props.roomCode">
                            Room {{ props.roomCode }} ·
                        </span>
                        {{ isMultiplayer ? 'Multiplayer match' : 'Solo vs AI' }}
                        · {{ props.session.difficulty }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span
                    class="rounded-full border px-3 py-1 text-xs font-semibold"
                    :class="statusTone"
                >
                    {{ statusLabel }}
                </span>

                <button
                    v-if="!isFinished"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-400/30 bg-rose-400/10 px-3 py-1.5 text-xs font-semibold text-rose-200 transition hover:bg-rose-400/20"
                    @click="resign"
                >
                    <Flag class="size-3.5" /> Resign
                </button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                <section class="panel p-5 sm:p-6">
                    <div
                        v-if="gameError"
                        class="mb-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-3 py-2 text-xs text-rose-200"
                    >
                        {{ gameError }}
                    </div>

                    <!-- Tic Tac Toe -->
                    <template v-if="props.game.slug === 'tic-tac-toe'">
                        <div
                            class="mx-auto grid max-w-md grid-cols-3 gap-2 sm:gap-3"
                        >
                            <button
                                v-for="(cell, index) in board"
                                :key="index"
                                type="button"
                                class="flex aspect-square items-center justify-center rounded-2xl border text-4xl font-black transition sm:text-5xl"
                                :class="[
                                    winningLine.includes(index)
                                        ? 'border-emerald-400/60 bg-emerald-400/15 text-emerald-200'
                                        : 'border-white/10 bg-white/[0.03] text-white hover:border-cyan-400/40 hover:bg-cyan-400/5',
                                    cell === null && !isFinished && myTurn
                                        ? 'cursor-pointer'
                                        : 'cursor-not-allowed',
                                ]"
                                :disabled="
                                    cell !== null ||
                                    isFinished ||
                                    !myTurn ||
                                    form.processing
                                "
                                @click="submitPosition(index)"
                            >
                                <span
                                    :class="
                                        cell === 'X'
                                            ? 'text-cyan-300'
                                            : cell === 'O'
                                              ? 'text-violet-300'
                                              : ''
                                    "
                                >
                                    {{ cell }}
                                </span>
                            </button>
                        </div>

                        <div
                            v-if="isFinished"
                            class="mt-6 flex flex-col items-center gap-2 text-center"
                        >
                            <p class="text-lg font-bold text-white">
                                {{
                                    props.session.is_draw
                                        ? "It's a draw"
                                        : statusLabel
                                }}
                            </p>

                            <Link
                                :href="`/games/${props.game.slug}`"
                                class="inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-[#06101a]"
                            >
                                <RotateCcw class="size-4" /> Play again
                            </Link>
                        </div>
                    </template>

                    <!-- Rock Paper Scissors -->
                    <template
                        v-else-if="props.game.slug === 'rock-paper-scissors'"
                    >
                        <div
                            class="flex items-center justify-center gap-8 text-center"
                        >
                            <div>
                                <p
                                    class="text-xs tracking-wide text-slate-500 uppercase"
                                >
                                    You
                                </p>
                                <p class="text-4xl font-black text-cyan-300">
                                    {{ myScore }}
                                </p>
                            </div>

                            <div class="text-xs text-slate-500">
                                Round {{ state.round }} /
                                {{ state.round_limit }}
                                <p class="mt-1 text-[10px] text-slate-600">
                                    First to {{ state.wins_needed }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="text-xs tracking-wide text-slate-500 uppercase"
                                >
                                    {{ isMultiplayer ? 'Opponent' : 'AI' }}
                                </p>
                                <p class="text-4xl font-black text-violet-300">
                                    {{ opponentScore }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 grid gap-3 sm:grid-cols-3">
                            <button
                                v-for="choice in ['rock', 'paper', 'scissors']"
                                :key="choice"
                                type="button"
                                class="group flex flex-col items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] py-6 transition hover:border-cyan-400/40 hover:bg-cyan-400/5 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="
                                    isFinished ||
                                    form.processing ||
                                    myChoiceLocked
                                "
                                @click="submitChoice(choice)"
                            >
                                <span
                                    class="text-4xl transition group-hover:scale-110"
                                >
                                    {{ choiceEmoji[choice] }}
                                </span>

                                <span
                                    class="text-sm font-semibold text-white capitalize"
                                >
                                    {{ choice }}
                                </span>
                            </button>
                        </div>

                        <p
                            v-if="myChoiceLocked && !isFinished"
                            class="mt-4 flex items-center justify-center gap-2 text-center text-sm text-cyan-200"
                        >
                            <Lock class="size-4" />
                            Choice locked — waiting for the round to resolve.
                        </p>

                        <div v-if="rpsRounds.length" class="mt-6">
                            <p
                                class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Round history
                            </p>

                            <div class="space-y-1.5">
                                <div
                                    v-for="round in [...rpsRounds].reverse()"
                                    :key="round.round"
                                    class="flex items-center justify-between gap-3 rounded-lg border border-white/5 bg-white/[0.02] px-3 py-2 text-xs"
                                >
                                    <span class="text-slate-500">
                                        Round {{ round.round }}
                                    </span>

                                    <span class="text-slate-300">
                                        {{
                                            choiceEmoji[
                                                round.choices[String(myId)]
                                            ] ?? '—'
                                        }}
                                        <span class="mx-1 text-slate-600"
                                            >vs</span
                                        >
                                        {{
                                            choiceEmoji[
                                                round.ai_choice ??
                                                    round.choices[
                                                        String(
                                                            props.session.players?.find(
                                                                (p) =>
                                                                    p.id !==
                                                                    myId,
                                                            )?.id ?? '',
                                                        )
                                                    ]
                                            ] ?? '—'
                                        }}
                                    </span>

                                    <span
                                        class="font-semibold"
                                        :class="
                                            round.winner_id === myId
                                                ? 'text-emerald-300'
                                                : round.winner_id === null
                                                  ? 'text-amber-300'
                                                  : 'text-rose-300'
                                        "
                                    >
                                        {{
                                            round.winner_id === myId
                                                ? 'Won'
                                                : round.winner_id === null
                                                  ? 'Draw'
                                                  : 'Lost'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="isFinished"
                            class="mt-6 flex flex-col items-center gap-2 text-center"
                        >
                            <p class="text-lg font-bold text-white">
                                {{ statusLabel }}
                            </p>

                            <Link
                                :href="`/games/${props.game.slug}`"
                                class="inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-[#06101a]"
                            >
                                <RotateCcw class="size-4" /> Play again
                            </Link>
                        </div>
                    </template>

                    <!-- Number Guess -->
                    <template v-else>
                        <div class="mx-auto max-w-md text-center">
                            <p
                                class="text-xs tracking-wide text-slate-500 uppercase"
                            >
                                Guess a number between
                            </p>

                            <p class="mt-1 text-3xl font-black text-white">
                                {{ state.min }} – {{ state.max }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ state.attempts_left }} of
                                {{ state.max_attempts }}
                                attempts left
                            </p>

                            <form
                                v-if="!isFinished"
                                class="mt-6 flex items-center gap-2"
                                @submit.prevent="submitGuess"
                            >
                                <input
                                    v-model.number="guessInput"
                                    type="number"
                                    :min="Number(state.min)"
                                    :max="Number(state.max)"
                                    required
                                    class="h-12 w-full rounded-xl border border-white/10 bg-white/5 px-4 text-center text-lg font-bold text-white outline-none focus:border-cyan-400/50"
                                    placeholder="Enter your guess"
                                />

                                <button
                                    type="submit"
                                    class="inline-flex h-12 items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-5 text-sm font-bold text-[#06101a] disabled:opacity-60"
                                    :disabled="form.processing"
                                >
                                    <LoaderCircle
                                        v-if="form.processing"
                                        class="size-4 animate-spin"
                                    />
                                    <Send v-else class="size-4" />
                                    Guess
                                </button>
                            </form>

                            <div
                                v-if="attempts.length"
                                class="mt-6 space-y-1.5 text-left"
                            >
                                <div
                                    v-for="(attempt, index) in attempts"
                                    :key="index"
                                    class="flex items-center justify-between gap-3 rounded-lg border border-white/5 bg-white/[0.02] px-3 py-2 text-sm"
                                >
                                    <span class="text-slate-300">
                                        Guess #{{ index + 1 }}:
                                        {{ attempt.guess }}
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1 text-xs font-semibold"
                                        :class="
                                            attempt.feedback === 'correct'
                                                ? 'text-emerald-300'
                                                : attempt.feedback === 'higher'
                                                  ? 'text-amber-300'
                                                  : 'text-rose-300'
                                        "
                                    >
                                        <Check
                                            v-if="
                                                attempt.feedback === 'correct'
                                            "
                                            class="size-3.5"
                                        />

                                        <span
                                            v-else-if="
                                                attempt.feedback === 'higher'
                                            "
                                        >
                                            Go higher
                                        </span>

                                        <span v-else>Go lower</span>
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="isFinished"
                                class="mt-6 flex flex-col items-center gap-2"
                            >
                                <p class="text-lg font-bold text-white">
                                    {{ statusLabel }}
                                </p>

                                <p
                                    v-if="state.secret_number"
                                    class="text-sm text-slate-400"
                                >
                                    The number was
                                    <span class="font-bold text-cyan-300">
                                        {{ state.secret_number }}
                                    </span>
                                </p>

                                <Link
                                    :href="`/games/${props.game.slug}`"
                                    class="mt-1 inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-[#06101a]"
                                >
                                    <RotateCcw class="size-4" /> Play again
                                </Link>
                            </div>
                        </div>
                    </template>
                </section>

                <section
                    v-if="
                        isFinished && (props.session.results?.length ?? 0) > 0
                    "
                    class="panel p-5"
                >
                    <h2
                        class="flex items-center gap-2 font-semibold text-white"
                    >
                        <Trophy class="size-4 text-amber-300" /> Match results
                    </h2>

                    <div class="mt-3 space-y-2">
                        <div
                            v-for="result in props.session.results"
                            :key="result.id"
                            class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5"
                        >
                            <PlayerAvatar
                                :src="result.user?.avatar_url"
                                :name="result.user?.name ?? 'Player'"
                                size="sm"
                            />

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-white"
                                >
                                    {{ result.user?.username ?? 'Player' }}

                                    <span
                                        v-if="result.user?.id === myId"
                                        class="text-xs text-cyan-300"
                                    >
                                        (you)
                                    </span>
                                </p>

                                <p class="text-xs text-slate-500">
                                    Score {{ result.score }}
                                </p>
                            </div>

                            <ResultBadge :result="result.result" />

                            <span
                                class="w-14 text-right text-sm font-bold"
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
                </section>
            </div>

            <aside class="space-y-4">
                <section class="panel p-5">
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-white"
                    >
                        <Users class="size-4 text-cyan-300" /> Players
                    </h2>

                    <div class="mt-3 space-y-2">
                        <div
                            v-for="player in props.session.players"
                            :key="player.id"
                            class="flex items-center gap-3 rounded-xl border px-3 py-2"
                            :class="
                                props.session.current_turn_user_id ===
                                    player.id && !isFinished
                                    ? 'border-cyan-400/40 bg-cyan-400/10'
                                    : 'border-white/10 bg-white/[0.03]'
                            "
                        >
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

                                    <span
                                        v-if="player.id === myId"
                                        class="text-xs text-cyan-300"
                                    >
                                        (you)
                                    </span>
                                </p>

                                <p class="text-xs text-slate-500">
                                    {{ marks[String(player.id)] ?? 'Player' }}
                                </p>
                            </div>

                            <span
                                v-if="
                                    props.session.current_turn_user_id ===
                                        player.id && !isFinished
                                "
                                class="text-[10px] font-semibold text-cyan-300 uppercase"
                            >
                                Turn
                            </span>
                        </div>

                        <div
                            v-if="!isMultiplayer"
                            class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2"
                        >
                            <div
                                class="flex size-8 items-center justify-center rounded-lg border border-violet-400/20 bg-violet-400/10 text-violet-300"
                            >
                                <Bot class="size-4" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-white">
                                    House AI
                                </p>
                                <p class="text-xs text-slate-500 capitalize">
                                    {{ props.session.difficulty }} difficulty
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel p-5">
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-white"
                    >
                        <Coins class="size-4 text-amber-300" /> Stakes
                    </h2>

                    <p class="mt-2 text-xs text-slate-400">
                        Win reward:
                        <span class="font-semibold text-emerald-300">
                            +{{ props.game.points_win }} points
                        </span>
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Points are awarded automatically by the server when the
                        match finishes.
                    </p>
                </section>

                <section class="panel p-5">
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-white"
                    >
                        <Info class="size-4 text-cyan-300" /> How to play
                    </h2>

                    <p class="mt-2 text-xs whitespace-pre-line text-slate-400">
                        {{ props.game.instructions }}
                    </p>
                </section>

                <Link
                    v-if="props.roomCode"
                    :href="`/rooms/${props.roomCode}`"
                    class="panel panel-hover flex items-center gap-2 p-4 text-sm font-medium text-slate-300"
                >
                    <ArrowLeft class="size-4" />
                    Back to room {{ props.roomCode }}
                </Link>

                <Link
                    v-else
                    :href="`/games/${props.game.slug}`"
                    class="panel panel-hover flex items-center gap-2 p-4 text-sm font-medium text-slate-300"
                >
                    <Gamepad2 class="size-4" />
                    Back to {{ props.game.name }}
                </Link>
            </aside>
        </div>
    </div>
</template>
