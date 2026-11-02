<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        src?: string | null;
        name: string;
        size?: 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        size: 'md',
    },
);

const sizeClass = computed(
    () =>
        ({
            sm: 'size-8 text-xs',
            md: 'size-10 text-sm',
            lg: 'size-14 text-base',
            xl: 'size-24 text-2xl',
        })[props.size],
);
</script>

<template>
    <Avatar class="overflow-hidden rounded-xl" :class="sizeClass">
        <AvatarImage v-if="src" :src="src" :alt="name" />
        <AvatarFallback
            class="rounded-xl bg-gradient-to-br from-cyan-400/25 to-violet-500/25 font-semibold text-white"
        >
            {{ getInitials(name) }}
        </AvatarFallback>
    </Avatar>
</template>
