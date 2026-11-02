<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Check,
    Coins,
    Gift,
    History,
    LoaderCircle,
    Lock,
    Palette,
    Sparkles,
} from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import StatTile from '@/components/StatTile.vue';
import type {
    Paginator,
    RewardItem,
    RewardTransactionItem,
} from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Rewards', href: '/rewards' }],
    },
});

const props = defineProps<{
    balance: number;
    rewards: RewardItem[];
    transactions: Paginator<RewardTransactionItem>;
}>();

const redeeming = ref<string | null>(null);

const redeem = (reward: RewardItem): void => {
    if (reward.owned || !reward.available || props.balance < reward.cost) {
        return;
    }

    redeeming.value = reward.slug;

    router.post(
        `/rewards/${reward.slug}/redeem`,
        {},
        {
            preserveScroll: true,
            onFinish: () => (redeeming.value = null),
        },
    );
};

const typeMeta: Record<string, { label: string; icon: unknown; tone: string }> =
    {
        title: {
            label: 'Title',
            icon: Sparkles,
            tone: 'text-cyan-300 border-cyan-400/20 bg-cyan-400/10',
        },
        badge: {
            label: 'Badge',
            icon: BadgeCheck,
            tone: 'text-violet-300 border-violet-400/20 bg-violet-400/10',
        },
        theme: {
            label: 'Theme',
            icon: Palette,
            tone: 'text-amber-300 border-amber-400/20 bg-amber-400/10',
        },
    };

const metaFor = (type: string) =>
    typeMeta[type] ?? {
        label: type,
        icon: Gift,
        tone: 'text-slate-300 border-white/10 bg-white/5',
    };

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString(undefined, {
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';

const number = (value: number): string => new Intl.NumberFormat().format(value);
</script>

<template>
    <Head title="Rewards" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Rewards store"
            title="Spend your points"
            subtitle="Trade hard-earned points for titles, badges and profile themes."
        />

        <section class="grid gap-4 sm:grid-cols-3">
            <StatTile
                label="Balance"
                :value="number(props.balance)"
                :icon="Coins"
                accent="amber"
                hint="Available to spend"
            />
            <StatTile
                label="Store items"
                :value="props.rewards.length"
                :icon="Gift"
                accent="violet"
            />
            <StatTile
                label="Transactions"
                :value="props.transactions.total"
                :icon="History"
                accent="cyan"
            />
        </section>

        <section class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-white">
                    Available rewards
                </h2>
                <span class="text-xs text-slate-500">
                    Prices are in arcade points
                </span>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="reward in props.rewards"
                    :key="reward.id"
                    class="panel panel-hover relative flex flex-col overflow-hidden p-5"
                >
                    <div
                        class="pointer-events-none absolute -top-16 -right-12 size-40 rounded-full bg-violet-500/10 blur-3xl"
                    />
                    <div
                        class="relative flex items-start justify-between gap-3"
                    >
                        <div
                            class="flex size-12 items-center justify-center rounded-2xl border"
                            :class="metaFor(reward.type).tone"
                        >
                            <GameIcon :name="reward.icon" class="size-6" />
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="stat-chip">{{
                                metaFor(reward.type).label
                            }}</span>
                            <span
                                v-if="reward.stock !== null"
                                class="text-[10px] text-slate-500"
                            >
                                {{ reward.stock }} left
                            </span>
                        </div>
                    </div>

                    <div class="relative mt-4 flex-1">
                        <h3 class="font-semibold text-white">
                            {{ reward.name }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-400">
                            {{ reward.description }}
                        </p>
                    </div>

                    <div
                        class="relative mt-4 flex items-center justify-between gap-3"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-300"
                        >
                            <Coins class="size-4" /> {{ number(reward.cost) }}
                        </span>

                        <span
                            v-if="reward.owned"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-300"
                        >
                            <Check class="size-3.5" /> Owned
                        </span>
                        <button
                            v-else-if="!reward.available"
                            type="button"
                            disabled
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-500"
                        >
                            <Lock class="size-3.5" /> Sold out
                        </button>
                        <button
                            v-else
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="
                                props.balance >= reward.cost
                                    ? 'bg-cyan-400 text-[#06101a] hover:bg-cyan-300'
                                    : 'border border-white/10 bg-white/5 text-slate-400'
                            "
                            :disabled="
                                props.balance < reward.cost ||
                                redeeming === reward.slug
                            "
                            @click="redeem(reward)"
                        >
                            <LoaderCircle
                                v-if="redeeming === reward.slug"
                                class="size-3.5 animate-spin"
                            />
                            <Coins v-else class="size-3.5" />
                            {{
                                props.balance >= reward.cost
                                    ? 'Redeem'
                                    : 'Not enough points'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 font-semibold text-white">
                    <History class="size-4 text-cyan-300" /> Points history
                </h2>
                <span class="text-xs text-slate-500">
                    {{ props.transactions.total }} transactions
                </span>
            </div>

            <div
                v-if="props.transactions.data.length"
                class="mt-4 overflow-x-auto"
            >
                <table class="w-full min-w-[620px] text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 uppercase">
                            <th class="pb-2 font-medium">Reason</th>
                            <th class="pb-2 font-medium">Type</th>
                            <th class="pb-2 text-right font-medium">Amount</th>
                            <th class="pb-2 text-right font-medium">Balance</th>
                            <th class="pb-2 text-right font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="transaction in props.transactions.data"
                            :key="transaction.id"
                            class="text-slate-300"
                        >
                            <td class="py-2.5">
                                <span class="flex items-center gap-2">
                                    <Gift
                                        v-if="transaction.reward"
                                        class="size-3.5 text-violet-300"
                                    />
                                    {{ transaction.reason }}
                                </span>
                            </td>
                            <td
                                class="py-2.5 text-xs text-slate-500 capitalize"
                            >
                                {{ String(transaction.type).replace('_', ' ') }}
                            </td>
                            <td
                                class="py-2.5 text-right font-semibold"
                                :class="
                                    transaction.amount >= 0
                                        ? 'text-emerald-300'
                                        : 'text-rose-300'
                                "
                            >
                                {{ transaction.amount >= 0 ? '+' : ''
                                }}{{ transaction.amount }}
                            </td>
                            <td class="py-2.5 text-right text-slate-400">
                                {{ number(transaction.balance_after) }}
                            </td>
                            <td
                                class="py-2.5 text-right text-xs text-slate-500"
                            >
                                {{ formatDate(transaction.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                class="mt-4"
                title="No transactions yet"
                description="Play matches to earn points and start spending."
            />

            <PaginationLinks class="mt-4" :paginator="props.transactions" />
        </section>
    </div>
</template>
