<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Coins, Lock, Medal, Sparkles, Trophy } from '@lucide/vue';
import { computed } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatTile from '@/components/StatTile.vue';
import type { AchievementItem } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Achievements', href: '/achievements' }],
    },
});

const props = defineProps<{
    achievements: AchievementItem[];
    summary: {
        unlocked: number;
        total: number;
        points_earned: number;
    };
}>();

const percent = computed(() =>
    props.summary.total === 0
        ? 0
        : Math.round((props.summary.unlocked / props.summary.total) * 100),
);

const groups = computed(() => {
    const labels: Record<string, string> = {
        wins: 'Wins',
        games_played: 'Games played',
        streak: 'Win streaks',
        points: 'Points milestones',
        multiplayer_wins: 'Multiplayer',
        tictactoe_wins: 'Tic-Tac-Toe',
        rps_wins: 'Rock Paper Scissors',
        number_guess_wins: 'Number Guess',
    };

    const grouped = new Map<string, AchievementItem[]>();

    for (const achievement of props.achievements) {
        const key = labels[achievement.criteria_type] ?? 'Other';
        grouped.set(key, [...(grouped.get(key) ?? []), achievement]);
    }

    return [...grouped.entries()].map(([label, items]) => ({ label, items }));
});

const tierTone: Record<string, string> = {
    bronze: 'border-orange-400/25 bg-orange-400/10 text-orange-300',
    silver: 'border-slate-300/25 bg-slate-300/10 text-slate-200',
    gold: 'border-amber-300/30 bg-amber-300/10 text-amber-300',
};

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString(undefined, {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          })
        : '';
</script>

<template>
    <Head title="Achievements" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Trophy cabinet"
            title="Achievements"
            subtitle="Unlock badges by playing, winning and climbing the leaderboard."
        >
            <template #actions>
                <Link
                    href="/games"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-4 py-2 text-sm font-bold text-[#06101a] transition hover:brightness-110"
                >
                    <Trophy class="size-4" /> Play a match
                </Link>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-3">
            <StatTile
                label="Unlocked"
                :value="`${props.summary.unlocked} / ${props.summary.total}`"
                :icon="Medal"
                accent="violet"
                :hint="`${percent}% of all achievements`"
            />
            <StatTile
                label="Points earned"
                :value="props.summary.points_earned"
                :icon="Coins"
                accent="amber"
                hint="From achievement rewards"
            />
            <StatTile
                label="Still locked"
                :value="props.summary.total - props.summary.unlocked"
                :icon="Lock"
                accent="cyan"
                hint="Keep playing to unlock"
            />
        </section>

        <section class="panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Sparkles class="size-4 text-cyan-300" />
                    <h2 class="font-semibold text-white">Overall progress</h2>
                </div>
                <span class="text-sm font-semibold text-cyan-300"
                    >{{ percent }}%</span
                >
            </div>
            <div class="mt-3">
                <ProgressBar
                    :value="props.summary.unlocked"
                    :max="Math.max(1, props.summary.total)"
                    accent="violet"
                />
            </div>
        </section>

        <section v-for="group in groups" :key="group.label" class="space-y-3">
            <h2 class="text-lg font-semibold text-white">{{ group.label }}</h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="achievement in group.items"
                    :key="achievement.slug"
                    class="panel relative overflow-hidden p-5"
                    :class="achievement.unlocked ? 'border-emerald-400/20' : ''"
                >
                    <div
                        class="pointer-events-none absolute -top-16 -right-12 size-40 rounded-full blur-3xl"
                        :class="
                            achievement.unlocked
                                ? 'bg-emerald-400/10'
                                : 'bg-white/[0.03]'
                        "
                    />
                    <div class="relative flex items-start gap-3">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-2xl border"
                            :class="
                                achievement.unlocked
                                    ? (tierTone[achievement.tier] ??
                                      tierTone.bronze)
                                    : 'border-white/10 bg-white/5 text-slate-500'
                            "
                        >
                            <GameIcon :name="achievement.icon" class="size-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <h3 class="truncate font-semibold text-white">
                                    {{ achievement.name }}
                                </h3>
                                <span
                                    class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase"
                                    :class="
                                        achievement.unlocked
                                            ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300'
                                            : 'border-white/10 bg-white/5 text-slate-400'
                                    "
                                >
                                    {{
                                        achievement.unlocked
                                            ? 'Unlocked'
                                            : 'Locked'
                                    }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ achievement.description }}
                            </p>
                        </div>
                    </div>

                    <div class="relative mt-4">
                        <div
                            class="flex items-center justify-between text-xs text-slate-500"
                        >
                            <span>
                                {{ achievement.progress }} /
                                {{ achievement.criteria_value }}
                            </span>
                            <span class="font-semibold text-amber-300">
                                +{{ achievement.points_reward }} pts
                            </span>
                        </div>
                        <div class="mt-1.5">
                            <ProgressBar
                                :value="achievement.progress"
                                :max="Math.max(1, achievement.criteria_value)"
                                :accent="
                                    achievement.unlocked ? 'emerald' : 'cyan'
                                "
                            />
                        </div>
                        <p
                            v-if="
                                achievement.unlocked && achievement.unlocked_at
                            "
                            class="mt-2 text-[10px] text-slate-500"
                        >
                            Unlocked {{ formatDate(achievement.unlocked_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
