<script setup lang="ts">
import { Gamepad2 } from '@lucide/vue';
</script>

<template>
    <div
        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-violet-500 text-[#06101a] shadow-[0_0_24px_-6px_rgba(34,211,238,0.8)]"
    >
        <Gamepad2 class="size-5" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm">
        <span class="truncate leading-tight font-bold text-white">
            Neon<span class="text-cyan-300">Arcade</span>
        </span>
        <span
            class="truncate text-[10px] tracking-widest text-slate-500 uppercase"
        >
            Multiplayer
        </span>
    </div>
</template>
