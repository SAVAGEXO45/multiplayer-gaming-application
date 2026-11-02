<script setup lang="ts">
import {
    BadgeCheck,
    CalendarCheck,
    Coins,
    Crown,
    Flame,
    Gamepad2,
    Gem,
    Grid3x3,
    Hash,
    Medal,
    Moon,
    Shield,
    Sparkles,
    Swords,
    Timer,
    Trophy,
    Users,
    Zap,
} from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        name?: string | null;
        class?: string;
    }>(),
    {
        name: 'gamepad-2',
        class: 'size-6',
    },
);

const icons: Record<string, typeof Gamepad2> = {
    'gamepad-2': Gamepad2,
    'grid-3x3': Grid3x3,
    swords: Swords,
    hash: Hash,
    trophy: Trophy,
    flame: Flame,
    medal: Medal,
    crown: Crown,
    'calendar-check': CalendarCheck,
    timer: Timer,
    zap: Zap,
    coins: Coins,
    gem: Gem,
    users: Users,
    sparkles: Sparkles,
    shield: Shield,
    'badge-check': BadgeCheck,
    moon: Moon,
};

const icon = computed(() => icons[props.name ?? 'gamepad-2'] ?? Gamepad2);
</script>

<template>
    <component :is="icon" :class="props.class" />
</template>
