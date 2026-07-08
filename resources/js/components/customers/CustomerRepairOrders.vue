<script setup lang="ts">
import RepairOrderStatusBadge from '@/components/repair-orders/RepairOrderStatusBadge.vue';
import { Link } from '@inertiajs/vue3';
import type { CustomerRepairOrder } from './types';
import { formatDateTime, vehicleSummary } from './utils';

defineProps<{
    repairOrders: CustomerRepairOrder[];
}>();
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Repair orders</h2>
        </div>

        <div v-if="repairOrders.length === 0" class="px-4 py-10 text-center text-sm text-muted-foreground">No repair orders yet.</div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Problem</th>
                        <th class="px-4 py-3">Vehicle</th>
                        <th class="px-4 py-3">Opened</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="repairOrder in repairOrders" :key="repairOrder.id">
                        <td class="whitespace-nowrap px-4 py-3 align-top">
                            <RepairOrderStatusBadge :status="repairOrder.status" />
                        </td>
                        <td class="max-w-xl px-4 py-3 align-top text-foreground">
                            <Link :href="repairOrder.showUrl" class="underline-offset-4 hover:underline">
                                {{ repairOrder.problemDescription ?? `Repair order #${repairOrder.id}` }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ repairOrder.vehicle ? vehicleSummary(repairOrder.vehicle) : '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ formatDateTime(repairOrder.openedAt) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
