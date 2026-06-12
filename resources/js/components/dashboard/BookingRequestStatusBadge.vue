<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';
import type { DashboardBookingRequestStatus, DashboardBookingRequestStatusValue } from './types';

const props = defineProps<{
    status: DashboardBookingRequestStatus;
}>();

const statusClasses: Record<DashboardBookingRequestStatusValue, string> = {
    new: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300',
    confirmed: 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/30 dark:text-green-300',
    rejected: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300',
    cancelled: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300',
};

const badgeClass = computed(() =>
    cn('inline-flex rounded-md border px-2 py-1 text-xs font-medium', statusClasses[props.status.value]),
);
</script>

<template>
    <span :class="badgeClass">
        {{ status.label }}
    </span>
</template>
