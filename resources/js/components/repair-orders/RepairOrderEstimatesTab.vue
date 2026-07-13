<script setup lang="ts">
import RepairOrderEstimatesSection from '@/components/repair-orders/RepairOrderEstimatesSection.vue';
import RepairOrderStatusActions from '@/components/repair-orders/RepairOrderStatusActions.vue';
import { computed } from 'vue';
import type { RepairOrderDetails } from './types';

const props = defineProps<{
    repairOrder: RepairOrderDetails;
    statusError?: string;
}>();

const latestEstimate = computed(() => props.repairOrder.estimates[0] ?? null);
</script>

<template>
    <section class="space-y-4">
        <div
            v-if="repairOrder.statusActions.canGenerateEstimate || statusError"
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <RepairOrderStatusActions
                :repair-order-id="repairOrder.id"
                :actions="{ canGenerateEstimate: repairOrder.statusActions.canGenerateEstimate, hasEstimate: repairOrder.statusActions.hasEstimate }"
                :latest-estimate="latestEstimate"
                :status-error="statusError"
            />
        </div>

        <RepairOrderEstimatesSection :estimates="repairOrder.estimates" />
    </section>
</template>
