<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';
import type { RepairOrderStatus, RepairOrderStatusValue } from './types';

const props = withDefaults(
    defineProps<{
        status: RepairOrderStatus;
        interactive?: boolean;
        disabled?: boolean;
    }>(),
    {
        interactive: false,
        disabled: false,
    },
);

const statusClasses: Record<RepairOrderStatusValue, string> = {
    draft: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-300',
    estimated: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300',
    in_progress: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/60 dark:bg-violet-950/30 dark:text-violet-300',
    completed: 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/30 dark:text-green-300',
    cancelled: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300',
};

const badgeClass = computed(() => [
    'inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium',
    statusClasses[props.status.value],
    props.interactive
        ? 'cursor-pointer transition-shadow hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-60'
        : '',
]);
</script>

<template>
    <button v-if="interactive" type="button" :disabled="disabled" :class="badgeClass">
        {{ status.label }}
        <ChevronDown class="size-3" />
    </button>

    <span v-else :class="badgeClass">
        {{ status.label }}
    </span>
</template>
