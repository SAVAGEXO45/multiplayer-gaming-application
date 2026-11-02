<script setup lang="ts">
import { Gamepad2 } from '@lucide/vue';
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: Component;
    }>(),
    {
        icon: () => Gamepad2,
    },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-12 text-center"
    >
        <div
            class="flex size-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-400"
        >
            <component :is="icon" class="size-6" />
        </div>
        <div>
            <p class="font-semibold text-white">{{ title }}</p>
            <p v-if="description" class="mt-1 text-sm text-slate-400">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.default" class="mt-2">
            <slot />
        </div>
    </div>
</template>
