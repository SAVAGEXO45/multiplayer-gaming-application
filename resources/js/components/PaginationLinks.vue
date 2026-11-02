<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Paginator } from '@/types/game';

const props = defineProps<{
    paginator: Paginator<unknown>;
}>();

const links = computed(() =>
    props.paginator.links.filter(
        (link) =>
            link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;',
    ),
);

const cleanLabel = (label: string): string =>
    label.replace('&laquo;', '«').replace('&raquo;', '»');
</script>

<template>
    <div
        v-if="paginator.last_page > 1"
        class="flex flex-wrap items-center justify-between gap-3"
    >
        <p class="text-xs text-slate-500">
            Showing {{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }} of
            {{ paginator.total }}
        </p>
        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            <Link
                v-if="paginator.prev_page_url"
                :href="paginator.prev_page_url"
                preserve-scroll
                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-cyan-400/40 hover:text-cyan-200"
            >
                Previous
            </Link>
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.url ?? '#'"
                preserve-scroll
                class="min-w-8 rounded-lg border px-3 py-1.5 text-center text-xs font-medium transition"
                :class="
                    link.active
                        ? 'border-cyan-400/50 bg-cyan-400/15 text-cyan-200'
                        : 'border-white/10 bg-white/5 text-slate-300 hover:border-cyan-400/40 hover:text-cyan-200'
                "
                :aria-current="link.active ? 'page' : undefined"
                v-html="cleanLabel(link.label)"
            />
            <Link
                v-if="paginator.next_page_url"
                :href="paginator.next_page_url"
                preserve-scroll
                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:border-cyan-400/40 hover:text-cyan-200"
            >
                Next
            </Link>
        </nav>
    </div>
</template>
