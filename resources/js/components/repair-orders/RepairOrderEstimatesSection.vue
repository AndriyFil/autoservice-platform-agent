<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { Download } from 'lucide-vue-next';
import type { RepairOrderEstimate } from './types';
import { formatCents, formatDateTime } from './utils';

defineProps<{
    estimates: RepairOrderEstimate[];
}>();
const { t } = useTranslations();
</script>

<template>
    <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.sections.estimates') }}</h2>
            <div class="text-sm text-muted-foreground">
                {{ estimates.length }} {{ t(estimates.length === 1 ? 'repair_orders.units.version_singular' : 'repair_orders.units.version_plural') }}
            </div>
        </div>

        <div v-if="estimates.length === 0" class="rounded-md border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
            {{ t('repair_orders.messages.no_estimate_pdfs') }}
        </div>

        <div v-else class="overflow-x-auto rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border">
                    <tr>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.version') }}</th>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.status') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('repair_orders.fields.total') }}</th>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.generated') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('repair_orders.fields.pdf') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="estimate in estimates" :key="estimate.id">
                        <td class="whitespace-nowrap px-3 py-2 font-medium text-foreground">v{{ estimate.version }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">{{ estimate.status.label }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right font-medium text-foreground">
                            {{ formatCents(estimate.totalCents) }} {{ estimate.currency }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">{{ formatDateTime(estimate.generatedAt) }}</td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end">
                                <Button v-if="estimate.document" as-child size="sm" variant="outline">
                                    <a :href="estimate.document.downloadUrl">
                                        <Download class="size-4" />
                                        {{ t('repair_orders.actions.download') }}
                                    </a>
                                </Button>
                                <span v-else class="text-sm text-muted-foreground">-</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
