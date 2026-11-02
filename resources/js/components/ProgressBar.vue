<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        max: number;
        accent?: 'cyan' | 'violet' | 'emerald' | 'amber';
    }>(),
    { accent: 'cyan' },
);

const percent = computed(() =>
    props.max <= 0
        ? 0
        : Math.min(
              100,
              Math.max(0, Math.round((props.value / props.max) * 100)),
          ),
);

const colors: Record<string, string> = {
    cyan: 'from-cyan-400 to-sky-500',
    violet: 'from-violet-400 to-fuchsia-500',
    emerald: 'from-emerald-400 to-teal-500',
    amber: 'from-amber-400 to-orange-500',
};
</script>

<template>
    <div class="h-2 w-full overflow-hidden rounded-full bg-white/10">
        <div
            class="h-full rounded-full bg-gradient-to-r transition-all duration-500"
            :class="colors[accent]"
            :style="{ width: `${percent}%` }"
        />
    </div>
</template>
