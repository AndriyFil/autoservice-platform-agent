<script setup lang="ts">
import RepairOrderLinesSection from '@/components/repair-orders/RepairOrderLinesSection.vue';
import RepairOrderTotalsSummary from '@/components/repair-orders/RepairOrderTotalsSummary.vue';
import type { RepairOrderDetails } from './types';

defineProps<{
    repairOrder: RepairOrderDetails;
}>();
</script>

<template>
    <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <RepairOrderLinesSection
            :repair-order-id="repairOrder.id"
            :lines="repairOrder.lines"
            :available-line-types="repairOrder.availableLineTypes"
        />

        <RepairOrderTotalsSummary :totals="repairOrder.workingTotals" />
    </div>
</template>
