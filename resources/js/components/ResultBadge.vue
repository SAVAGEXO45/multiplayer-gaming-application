<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    result: string;
}>();

const style = computed(() => {
    switch (props.result) {
        case 'win':
            return 'border-emerald-400/30 bg-emerald-400/15 text-emerald-300';
        case 'loss':
            return 'border-rose-400/30 bg-rose-400/15 text-rose-300';
        default:
            return 'border-amber-400/30 bg-amber-400/15 text-amber-300';
    }
});

const label = computed(
    () =>
        ({
            win: 'Win',
            loss: 'Loss',
            draw: 'Draw',
        })[props.result] ?? props.result,
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize"
        :class="style"
    >
        {{ label }}
    </span>
</template>
