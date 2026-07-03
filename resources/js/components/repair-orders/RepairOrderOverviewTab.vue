<script setup lang="ts">
import RepairOrderStatusBadge from '@/components/repair-orders/RepairOrderStatusBadge.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { RepairOrderDetails } from './types';
import { formatCents, formatDateTime, vehicleSummary } from './utils';

const props = defineProps<{
    repairOrder: RepairOrderDetails;
}>();
const { t } = useTranslations();

const latestEstimate = () => props.repairOrder.estimates[0] ?? null;
</script>

<template>
    <section class="space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.tabs.overview') }}</h2>
            <RepairOrderStatusBadge :status="repairOrder.status" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.customer') }}</div>
                <div class="mt-1 text-sm font-medium text-foreground">{{ repairOrder.customer.name }}</div>
                <div class="text-sm text-muted-foreground">{{ repairOrder.customer.phone }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.vehicle') }}</div>
                <div class="mt-1 text-sm text-foreground">{{ vehicleSummary(repairOrder.vehicle, t('repair_orders.messages.no_vehicle')) }}</div>
            </div>

            <div class="md:col-span-2">
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.problem') }}</div>
                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-foreground">{{ repairOrder.problemDescription }}</p>
            </div>

            <div v-if="repairOrder.bookingRequest?.originalMessage" class="md:col-span-2">
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.original_message') }}</div>
                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-foreground">{{ repairOrder.bookingRequest.originalMessage }}</p>
            </div>

            <div v-else>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.source') }}</div>
                <div class="mt-1 text-sm text-foreground">
                    {{
                        repairOrder.bookingRequest
                            ? t('repair_orders.messages.booking_request_source')
                            : t('repair_orders.messages.manual_repair_order')
                    }}
                </div>
            </div>
        </div>

        <div class="grid gap-4 border-t border-sidebar-border/70 pt-4 dark:border-sidebar-border md:grid-cols-2">
            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.sections.working_totals') }}</div>
                <div class="mt-1 text-lg font-semibold text-foreground">{{ formatCents(repairOrder.workingTotals.totalCents) }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.sections.latest_estimate') }}</div>
                <div v-if="latestEstimate()" class="mt-1 text-sm text-foreground">
                    v{{ latestEstimate()?.version }} · {{ latestEstimate()?.status.label }} · {{ formatCents(latestEstimate()?.totalCents ?? 0) }}
                    <span class="text-muted-foreground">· {{ formatDateTime(latestEstimate()?.generatedAt ?? null) }}</span>
                </div>
                <div v-else class="mt-1 text-sm text-muted-foreground">{{ t('repair_orders.messages.no_estimate_summary') }}</div>
            </div>
        </div>
    </section>
</template>
