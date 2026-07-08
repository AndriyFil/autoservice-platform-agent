<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import RepairOrderEmptyState from './RepairOrderEmptyState.vue';
import RepairOrderStatusDropdown from './RepairOrderStatusDropdown.vue';
import type { RepairOrderListItem } from './types';
import { formatDateTime, vehicleSummary } from './utils';

defineProps<{
    repairOrders: RepairOrderListItem[];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Repair orders</h2>
        </div>

        <RepairOrderEmptyState v-if="repairOrders.length === 0" />

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Problem</th>
                        <th class="px-4 py-3">Vehicle</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Opened</th>
                        <th class="px-4 py-3">Closed</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="repairOrder in repairOrders" :key="repairOrder.id">
                        <td class="px-4 py-3 align-top">
                            <Link
                                :href="route('dashboard.repair-orders.show', { repairOrder: repairOrder.id })"
                                class="font-medium text-foreground underline-offset-4 hover:underline"
                            >
                                {{ repairOrder.customerName }}
                            </Link>
                        </td>
                        <td class="max-w-md px-4 py-3 align-top text-foreground">
                            {{ repairOrder.problemDescription }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ vehicleSummary(repairOrder.vehicle) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top">
                            <RepairOrderStatusDropdown
                                :repair-order-id="repairOrder.id"
                                :status="repairOrder.status"
                                :transitions="repairOrder.availableStatusTransitions"
                            />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ formatDateTime(repairOrder.openedAt) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ formatDateTime(repairOrder.closedAt) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                            <Button as-child size="sm" variant="outline">
                                <Link :href="route('dashboard.repair-orders.show', { repairOrder: repairOrder.id })">
                                    <Eye class="size-4" />
                                    View
                                </Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
