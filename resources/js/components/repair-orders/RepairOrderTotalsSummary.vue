<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { RepairOrderEstimateTotals } from './types';
import { formatCents } from './utils';

defineProps<{
    totals: RepairOrderEstimateTotals;
}>();
const { t } = useTranslations();
</script>

<template>
    <section class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.sections.working_totals') }}</h2>

        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-muted-foreground">{{ t('repair_orders.totals.subtotal') }}</dt>
                <dd class="font-medium text-foreground">{{ formatCents(totals.subtotalCents) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-muted-foreground">{{ t('repair_orders.totals.tax') }}</dt>
                <dd class="font-medium text-foreground">{{ formatCents(totals.taxCents) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 border-t border-sidebar-border/70 pt-3 dark:border-sidebar-border">
                <dt class="font-semibold text-foreground">{{ t('repair_orders.totals.total') }}</dt>
                <dd class="text-base font-semibold text-foreground">{{ formatCents(totals.totalCents) }}</dd>
            </div>
        </dl>
    </section>
</template>
