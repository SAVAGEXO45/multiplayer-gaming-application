<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Ban,
    CheckCircle2,
    Crown,
    Search,
    ShieldCheck,
    TriangleAlert,
    UserCog,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import type { Paginator } from '@/types/game';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Users', href: '/admin/users' },
        ],
    },
});

type AdminUser = {
    id: number;
    name: string;
    username: string;
    email: string;
    avatar_url: string;
    role: 'player' | 'admin';
    is_suspended: boolean;
    suspension_reason: string | null;
    points: number;
    games_played: number;
    wins: number;
    win_rate: number;
    created_at: string | null;
};

const props = defineProps<{
    users: Paginator<AdminUser>;
    filters: {
        search: string;
        role: string | null;
        status: string | null;
    };
}>();

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const status = ref(props.filters.status ?? '');

const applyFilters = (): void => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: role.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetFilters = (): void => {
    search.value = '';
    role.value = '';
    status.value = '';
    applyFilters();
};

const suspendTarget = ref<AdminUser | null>(null);
const suspendForm = useForm({ action: 'suspend', reason: '' });

const suspendUserError = computed(() => {
    const errors = suspendForm.errors as Record<string, string>;
    return errors.user ?? null;
});

const openSuspend = (user: AdminUser): void => {
    suspendTarget.value = user;
    suspendForm.action = user.is_suspended ? 'reactivate' : 'suspend';
    suspendForm.reason = '';
    suspendForm.clearErrors();
};

const closeSuspend = (): void => {
    suspendTarget.value = null;
    suspendForm.reset();
};

const submitStatus = (): void => {
    if (!suspendTarget.value) {
        return;
    }

    suspendForm.patch(`/admin/users/${suspendTarget.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => closeSuspend(),
    });
};

const toggleRole = (user: AdminUser): void => {
    router.patch(
        `/admin/users/${user.id}/role`,
        { role: user.role === 'admin' ? 'player' : 'admin' },
        { preserveScroll: true },
    );
};

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString(undefined, {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          })
        : '—';
</script>

<template>
    <Head title="Admin — Users" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Users"
            subtitle="Search accounts, adjust roles and suspend or reactivate players."
        />

        <section class="panel p-4">
            <form
                class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto]"
                @submit.prevent="applyFilters"
            >
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-500"
                    />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search name, gamer tag or email"
                        class="h-10 w-full rounded-xl border border-white/10 bg-white/5 pr-3 pl-9 text-sm text-white outline-none focus:border-cyan-400/50"
                    />
                </div>
                <select
                    v-model="role"
                    class="h-10 rounded-xl border border-white/10 bg-[#0b1020] px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                >
                    <option value="">All roles</option>
                    <option value="player">Players</option>
                    <option value="admin">Admins</option>
                </select>
                <select
                    v-model="status"
                    class="h-10 rounded-xl border border-white/10 bg-[#0b1020] px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-xl bg-cyan-400 px-4 text-sm font-bold text-[#06101a]"
                    >
                        Filter
                    </button>
                    <button
                        type="button"
                        class="h-10 rounded-xl border border-white/10 bg-white/5 px-3 text-sm text-slate-300"
                        @click="resetFilters"
                    >
                        Reset
                    </button>
                </div>
            </form>
        </section>

        <section v-if="suspendTarget" class="panel border-amber-400/25 p-5">
            <div class="flex items-start gap-3">
                <TriangleAlert class="mt-0.5 size-5 shrink-0 text-amber-300" />
                <div class="flex-1">
                    <h2 class="font-semibold text-white">
                        {{
                            suspendTarget.is_suspended
                                ? 'Reactivate'
                                : 'Suspend'
                        }}
                        {{ suspendTarget.username }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        {{
                            suspendTarget.is_suspended
                                ? 'The account will be able to sign in and play again.'
                                : 'The account will be signed out and blocked from playing.'
                        }}
                    </p>

                    <form class="mt-4 space-y-3" @submit.prevent="submitStatus">
                        <div v-if="suspendForm.action === 'suspend'">
                            <label class="field-label" for="reason"
                                >Reason</label
                            >
                            <input
                                id="reason"
                                v-model="suspendForm.reason"
                                type="text"
                                maxlength="255"
                                required
                                placeholder="Why is this account being suspended?"
                                class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-white/5 px-3 text-sm text-white outline-none focus:border-cyan-400/50"
                            />
                            <p
                                v-if="suspendForm.errors.reason"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ suspendForm.errors.reason }}
                            </p>
                        </div>

                        <p
                            v-if="suspendUserError"
                            class="text-xs text-rose-300"
                        >
                            {{ suspendUserError }}
                        </p>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="rounded-xl px-4 py-2 text-sm font-bold disabled:opacity-60"
                                :class="
                                    suspendForm.action === 'suspend'
                                        ? 'bg-rose-500 text-white'
                                        : 'bg-emerald-400 text-[#06101a]'
                                "
                                :disabled="suspendForm.processing"
                            >
                                {{
                                    suspendForm.action === 'suspend'
                                        ? 'Confirm suspension'
                                        : 'Reactivate account'
                                }}
                            </button>
                            <button
                                type="button"
                                class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300"
                                @click="closeSuspend"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="table-shell">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-sm">
                    <thead class="border-b border-white/10 bg-white/[0.03]">
                        <tr class="text-left text-xs text-slate-500 uppercase">
                            <th class="px-4 py-3 font-medium">Player</th>
                            <th class="px-4 py-3 font-medium">Role</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Points
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Games
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Win %
                            </th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Joined</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="user in props.users.data"
                            :key="user.id"
                            class="transition hover:bg-white/[0.03]"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <PlayerAvatar
                                        :src="user.avatar_url"
                                        :name="user.name"
                                        size="sm"
                                    />
                                    <div class="min-w-0">
                                        <Link
                                            :href="`/players/${user.username}`"
                                            class="block truncate font-medium text-white hover:text-cyan-200"
                                        >
                                            {{ user.username }}
                                        </Link>
                                        <p
                                            class="truncate text-xs text-slate-500"
                                        >
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase"
                                    :class="
                                        user.role === 'admin'
                                            ? 'border-violet-400/30 bg-violet-400/10 text-violet-300'
                                            : 'border-white/10 bg-white/5 text-slate-400'
                                    "
                                >
                                    <Crown
                                        v-if="user.role === 'admin'"
                                        class="size-3"
                                    />
                                    {{ user.role }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-right font-semibold text-cyan-300"
                            >
                                {{ user.points }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-300">
                                {{ user.games_played }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-300">
                                {{ user.win_rate }}%
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="user.is_suspended"
                                    class="inline-flex items-center gap-1 rounded-full border border-rose-400/30 bg-rose-400/10 px-2 py-0.5 text-[10px] font-semibold text-rose-300"
                                    :title="user.suspension_reason ?? undefined"
                                >
                                    <Ban class="size-3" /> Suspended
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-300"
                                >
                                    <CheckCircle2 class="size-3" /> Active
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-xs font-medium text-slate-300 transition hover:border-violet-400/40 hover:text-violet-200"
                                        @click="toggleRole(user)"
                                    >
                                        <ShieldCheck class="size-3.5" />
                                        {{
                                            user.role === 'admin'
                                                ? 'Demote'
                                                : 'Promote'
                                        }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition"
                                        :class="
                                            user.is_suspended
                                                ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300 hover:bg-emerald-400/20'
                                                : 'border-rose-400/30 bg-rose-400/10 text-rose-300 hover:bg-rose-400/20'
                                        "
                                        @click="openSuspend(user)"
                                    >
                                        <UserCog class="size-3.5" />
                                        {{
                                            user.is_suspended
                                                ? 'Reactivate'
                                                : 'Suspend'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p
                v-if="props.users.data.length === 0"
                class="px-4 py-10 text-center text-sm text-slate-500"
            >
                No users match these filters.
            </p>
        </section>

        <PaginationLinks :paginator="props.users" />
    </div>
</template>
