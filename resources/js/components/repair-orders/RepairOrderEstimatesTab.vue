<script setup lang="ts">
import RepairOrderEstimatesSection from '@/components/repair-orders/RepairOrderEstimatesSection.vue';
import RepairOrderStatusActions from '@/components/repair-orders/RepairOrderStatusActions.vue';
import type { RepairOrderDetails } from './types';

defineProps<{
    repairOrder: RepairOrderDetails;
    statusError?: string;
}>();
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
                :status-error="statusError"
            />
        </div>

        <RepairOrderEstimatesSection :estimates="repairOrder.estimates" />
    </section>
</template>
