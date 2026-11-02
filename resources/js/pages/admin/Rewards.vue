<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Coins, Gift, Plus, Save, ToggleLeft, ToggleRight } from '@lucide/vue';
import { reactive, ref } from 'vue';
import GameIcon from '@/components/GameIcon.vue';
import PageHeader from '@/components/PageHeader.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Rewards', href: '/admin/rewards' },
        ],
    },
});

type AdminReward = {
    id: number;
    slug: string;
    name: string;
    description: string;
    type: string;
    icon: string;
    cost: number;
    stock: number | null;
    is_active: boolean;
    claims: number;
};

const props = defineProps<{
    rewards: AdminReward[];
}>();

const drafts = reactive<
    Record<number, { cost: number; stock: number | null; is_active: boolean }>
>(
    Object.fromEntries(
        props.rewards.map((reward) => [
            reward.id,
            {
                cost: reward.cost,
                stock: reward.stock,
                is_active: reward.is_active,
            },
        ]),
    ),
);

const saving = ref<number | null>(null);

const save = (reward: AdminReward): void => {
    saving.value = reward.id;

    router.patch(`/admin/rewards/${reward.slug}`, drafts[reward.id], {
        preserveScroll: true,
        onFinish: () => (saving.value = null),
    });
};

const toggle = (reward: AdminReward): void => {
    drafts[reward.id].is_active = !drafts[reward.id].is_active;
};

const createForm = useForm({
    name: '',
    slug: '',
    description: '',
    type: 'title',
    icon: 'sparkles',
    cost: 100,
    stock: null as number | null,
    payload: '',
    is_active: true,
});

const submitCreate = (): void => {
    createForm.post('/admin/rewards', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const typeTone: Record<string, string> = {
    title: 'border-cyan-400/25 bg-cyan-400/10 text-cyan-300',
    badge: 'border-violet-400/25 bg-violet-400/10 text-violet-300',
    theme: 'border-amber-400/25 bg-amber-400/10 text-amber-300',
};
</script>

<template>
    <Head title="Admin — Rewards" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Rewards"
            subtitle="Create store items, adjust prices and control stock and availability."
        />

        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
            <section class="space-y-4">
                <div
                    v-for="reward in props.rewards"
                    :key="reward.id"
                    class="panel p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-11 items-center justify-center rounded-xl border"
                                :class="typeTone[reward.type] ?? typeTone.title"
                            >
                                <GameIcon :name="reward.icon" class="size-5" />
                            </div>
                            <div>
                                <h2 class="font-semibold text-white">
                                    {{ reward.name }}
                                </h2>
                                <p class="text-xs text-slate-500">
                                    {{ reward.slug }} ·
                                    {{ reward.claims }} claims
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                            :class="
                                drafts[reward.id].is_active
                                    ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300'
                                    : 'border-white/10 bg-white/5 text-slate-400'
                            "
                            @click="toggle(reward)"
                        >
                            <ToggleRight
                                v-if="drafts[reward.id].is_active"
                                class="size-4"
                            />
                            <ToggleLeft v-else class="size-4" />
                            {{
                                drafts[reward.id].is_active
                                    ? 'Active'
                                    : 'Hidden'
                            }}
                        </button>
                    </div>

                    <p class="mt-3 text-sm text-slate-400">
                        {{ reward.description }}
                    </p>

                    <div class="mt-4 flex flex-wrap items-end gap-3">
                        <label class="grid gap-1">
                            <span class="field-label">Cost</span>
                            <input
                                v-model.number="drafts[reward.id].cost"
                                type="number"
                                min="1"
                                max="1000000"
                                class="h-9 w-28 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                            />
                        </label>
                        <label class="grid gap-1">
                            <span class="field-label"
                                >Stock (blank = unlimited)</span
                            >
                            <input
                                v-model.number="drafts[reward.id].stock"
                                type="number"
                                min="0"
                                max="1000000"
                                placeholder="Unlimited"
                                class="h-9 w-40 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                            />
                        </label>
                        <button
                            type="button"
                            class="ml-auto inline-flex items-center gap-2 rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-[#06101a] transition hover:bg-cyan-300 disabled:opacity-60"
                            :disabled="saving === reward.id"
                            @click="save(reward)"
                        >
                            <Save class="size-4" />
                            {{ saving === reward.id ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                </div>
            </section>

            <aside>
                <form
                    class="panel space-y-4 p-5"
                    @submit.prevent="submitCreate"
                >
                    <h2
                        class="flex items-center gap-2 font-semibold text-white"
                    >
                        <Plus class="size-4 text-cyan-300" /> New reward
                    </h2>

                    <div class="grid gap-2">
                        <label class="field-label" for="reward-name"
                            >Name</label
                        >
                        <input
                            id="reward-name"
                            v-model="createForm.name"
                            type="text"
                            required
                            maxlength="80"
                            class="h-9 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                        <p
                            v-if="createForm.errors.name"
                            class="text-xs text-rose-300"
                        >
                            {{ createForm.errors.name }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <label class="field-label" for="reward-slug"
                            >Slug</label
                        >
                        <input
                            id="reward-slug"
                            v-model="createForm.slug"
                            type="text"
                            required
                            maxlength="64"
                            placeholder="neon-legend-title"
                            class="h-9 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                        <p
                            v-if="createForm.errors.slug"
                            class="text-xs text-rose-300"
                        >
                            {{ createForm.errors.slug }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <label class="field-label" for="reward-description"
                            >Description</label
                        >
                        <textarea
                            id="reward-description"
                            v-model="createForm.description"
                            rows="2"
                            required
                            maxlength="255"
                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400/50"
                        />
                        <p
                            v-if="createForm.errors.description"
                            class="text-xs text-rose-300"
                        >
                            {{ createForm.errors.description }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid gap-1">
                            <span class="field-label">Type</span>
                            <select
                                v-model="createForm.type"
                                class="h-9 rounded-lg border border-white/10 bg-[#0b1020] px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                            >
                                <option value="title">Title</option>
                                <option value="badge">Badge</option>
                                <option value="theme">Theme</option>
                            </select>
                        </label>
                        <label class="grid gap-1">
                            <span class="field-label">Icon</span>
                            <select
                                v-model="createForm.icon"
                                class="h-9 rounded-lg border border-white/10 bg-[#0b1020] px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                            >
                                <option value="sparkles">Sparkles</option>
                                <option value="badge-check">Badge</option>
                                <option value="crown">Crown</option>
                                <option value="shield">Shield</option>
                                <option value="flame">Flame</option>
                                <option value="gem">Gem</option>
                                <option value="moon">Moon</option>
                                <option value="gamepad-2">Gamepad</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid gap-1">
                            <span class="field-label">Cost</span>
                            <input
                                v-model.number="createForm.cost"
                                type="number"
                                min="1"
                                max="1000000"
                                required
                                class="h-9 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                            />
                        </label>
                        <label class="grid gap-1">
                            <span class="field-label">Stock</span>
                            <input
                                v-model.number="createForm.stock"
                                type="number"
                                min="0"
                                placeholder="Unlimited"
                                class="h-9 rounded-lg border border-white/10 bg-white/5 px-2 text-sm text-white outline-none focus:border-cyan-400/50"
                            />
                        </label>
                    </div>

                    <label
                        class="flex items-center gap-2 text-sm text-slate-300"
                    >
                        <input
                            v-model="createForm.is_active"
                            type="checkbox"
                            class="size-4 rounded border-white/20 bg-white/10 accent-cyan-400"
                        />
                        Visible in the store
                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-400 to-sky-500 px-4 py-2.5 text-sm font-bold text-[#06101a] transition hover:brightness-110 disabled:opacity-60"
                        :disabled="createForm.processing"
                    >
                        <Gift class="size-4" /> Create reward
                    </button>
                </form>

                <div
                    class="panel mt-4 flex items-start gap-2 p-4 text-xs text-slate-400"
                >
                    <Coins class="mt-0.5 size-4 shrink-0 text-amber-300" />
                    <p>
                        Rewards are deducted atomically from the player's
                        balance. Duplicate purchases are rejected by the server.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>
