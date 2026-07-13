<script setup lang="ts">
import RepairOrderStatusBadge from '@/components/repair-orders/RepairOrderStatusBadge.vue';
import { useTranslations } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import type { RepairOrderDetails } from './types';
import { formatCents, vehicleSummary } from './utils';

const props = defineProps<{
    repairOrder: RepairOrderDetails;
}>();
const { t } = useTranslations();
</script>

<template>
    <section class="space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.tabs.overview') }}</h2>
            <RepairOrderStatusBadge :status="props.repairOrder.status" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.customer') }}</div>
                <Link
                    :href="route('customers.show', { customer: props.repairOrder.customer.id })"
                    class="mt-1 block text-sm font-medium text-foreground underline-offset-4 hover:underline"
                >
                    {{ props.repairOrder.customer.name ?? 'Unnamed customer' }}
                </Link>
                <div class="text-sm text-muted-foreground">{{ props.repairOrder.customer.phone }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.vehicle') }}</div>
                <div class="mt-1 text-sm text-foreground">
                    {{ vehicleSummary(props.repairOrder.vehicle, t('repair_orders.messages.no_vehicle')) }}
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.problem') }}</div>
                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-foreground">{{ props.repairOrder.problemDescription }}</p>
            </div>

            <div v-if="props.repairOrder.bookingRequest?.originalMessage" class="md:col-span-2">
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.original_message') }}</div>
                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-foreground">{{ props.repairOrder.bookingRequest.originalMessage }}</p>
            </div>

            <div v-else>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.source') }}</div>
                <div class="mt-1 text-sm text-foreground">
                    {{
                        props.repairOrder.bookingRequest
                            ? t('repair_orders.messages.booking_request_source')
                            : t('repair_orders.messages.manual_repair_order')
                    }}
                </div>
            </div>
        </div>

        <div class="border-t border-sidebar-border/70 pt-4 dark:border-sidebar-border">
            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.sections.working_totals') }}</div>
                <div class="mt-1 text-lg font-semibold text-foreground">{{ formatCents(props.repairOrder.workingTotals.totalCents) }}</div>
            </div>
        </div>
    </section>
</template>
