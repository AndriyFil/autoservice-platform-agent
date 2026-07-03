<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import { ClipboardList } from 'lucide-vue-next';
import type { RepairOrderDetails } from './types';
import { formatDate, formatDateTime } from './utils';

defineProps<{
    repairOrder: RepairOrderDetails;
}>();
const { t } = useTranslations();
</script>

<template>
    <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.tabs.timeline') }}</h2>

        <div class="space-y-3 text-sm">
            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.opened') }}</div>
                <div class="mt-1 text-foreground">{{ formatDateTime(repairOrder.openedAt) }}</div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.closed') }}</div>
                <div class="mt-1 text-foreground">{{ formatDateTime(repairOrder.closedAt) }}</div>
            </div>

            <div v-if="repairOrder.bookingRequest">
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.source_request') }}</div>
                <Button as-child size="sm" variant="outline" class="mt-1">
                    <Link :href="route('dashboard.booking-requests.show', { bookingRequest: repairOrder.bookingRequest.id })">
                        <ClipboardList class="size-4" />
                        {{ t('repair_orders.actions.view_booking_request') }}
                    </Link>
                </Button>
            </div>

            <div v-if="repairOrder.bookingRequest?.originalMessage">
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.original_message') }}</div>
                <p class="mt-1 whitespace-pre-line text-foreground">{{ repairOrder.bookingRequest.originalMessage }}</p>
            </div>

            <div v-if="repairOrder.bookingRequest">
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.preferred_date') }}</div>
                <div class="mt-1 text-foreground">{{ formatDate(repairOrder.bookingRequest.preferredDate) }}</div>
            </div>

            <div v-else>
                <div class="text-xs font-medium uppercase text-muted-foreground">{{ t('repair_orders.fields.source') }}</div>
                <div class="mt-1 text-foreground">{{ t('repair_orders.messages.manual_repair_order') }}</div>
            </div>
        </div>
    </section>
</template>
