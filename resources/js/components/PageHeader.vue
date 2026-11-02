<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
    eyebrow?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
        <div class="space-y-1">
            <p
                v-if="eyebrow"
                class="text-xs font-semibold tracking-[0.2em] text-cyan-300/80 uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="text-2xl font-bold tracking-tight text-white sm:text-3xl"
            >
                {{ title }}
            </h1>
            <p v-if="subtitle" class="max-w-2xl text-sm text-slate-400">
                {{ subtitle }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
