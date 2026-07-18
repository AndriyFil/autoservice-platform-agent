<script setup lang="ts">
import { Wrench } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);
</script>

<template>
    <span class="inline-flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-[0.9rem] bg-[#0e7c86] text-white shadow-sm">
            <Wrench class="size-[1.15rem]" stroke-width="2.25" aria-hidden="true" />
        </span>
        <span class="min-w-0">
            <span class="block text-[1.05rem] font-bold leading-5 tracking-[-0.025em] text-[#0b1f33]">AutoService</span>
            <span v-if="!compact" class="mt-0.5 block text-xs leading-4 text-[#607086]">Your car. Our care.</span>
        </span>
    </span>
</template>
