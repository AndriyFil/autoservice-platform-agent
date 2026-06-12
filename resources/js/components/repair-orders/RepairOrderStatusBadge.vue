<script setup lang="ts">
import type { RepairOrderStatus, RepairOrderStatusValue } from './types';

defineProps<{
    status: RepairOrderStatus;
}>();

const statusClasses: Record<RepairOrderStatusValue, string> = {
    open: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300',
    completed: 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/30 dark:text-green-300',
    cancelled: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300',
};
</script>

<template>
    <span class="inline-flex rounded-md border px-2 py-1 text-xs font-medium" :class="statusClasses[status.value]">
        {{ status.label }}
    </span>
</template>
