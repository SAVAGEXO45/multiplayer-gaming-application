<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        icon?: Component;
        hint?: string;
        accent?: 'cyan' | 'violet' | 'emerald' | 'amber' | 'rose';
    }>(),
    {
        accent: 'cyan',
    },
);

const accents: Record<string, string> = {
    cyan: 'from-cyan-400/20 text-cyan-300 border-cyan-400/20',
    violet: 'from-violet-400/20 text-violet-300 border-violet-400/20',
    emerald: 'from-emerald-400/20 text-emerald-300 border-emerald-400/20',
    amber: 'from-amber-400/20 text-amber-300 border-amber-400/20',
    rose: 'from-rose-400/20 text-rose-300 border-rose-400/20',
};
</script>

<template>
    <div class="panel panel-hover relative overflow-hidden p-5">
        <div
            class="pointer-events-none absolute inset-x-0 -top-16 h-32 bg-gradient-to-b to-transparent opacity-60"
            :class="accents[accent]"
        />
        <div class="relative flex items-start justify-between gap-3">
            <div>
                <p
                    class="text-xs font-medium tracking-wide text-slate-400 uppercase"
                >
                    {{ label }}
                </p>
                <p class="mt-2 text-2xl font-bold text-white">{{ value }}</p>
                <p v-if="hint" class="mt-1 text-xs text-slate-500">
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon"
                class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5"
                :class="accents[accent]"
            >
                <component :is="icon" class="size-5" />
            </div>
        </div>
    </div>
</template>
