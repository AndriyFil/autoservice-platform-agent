<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { Download } from 'lucide-vue-next';
import type { RepairOrderDocument } from './types';
import { formatDateTime } from './utils';

defineProps<{
    documents: RepairOrderDocument[];
}>();
const { t } = useTranslations();
</script>

<template>
    <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.tabs.documents') }}</h2>
            <div class="text-sm text-muted-foreground">
                {{ documents.length }}
                {{ t(documents.length === 1 ? 'repair_orders.units.document_singular' : 'repair_orders.units.document_plural') }}
            </div>
        </div>

        <div
            v-if="documents.length === 0"
            class="rounded-md border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            {{ t('repair_orders.messages.no_documents') }}
        </div>

        <div v-else class="overflow-x-auto rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead
                    class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.filename') }}</th>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.type') }}</th>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.status') }}</th>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.generated') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('repair_orders.actions.download') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="document in documents" :key="document.id">
                        <td class="px-3 py-2 font-medium text-foreground">{{ document.filename }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">{{ document.type.label }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">{{ document.status.label }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">{{ formatDateTime(document.generatedAt) }}</td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end">
                                <Button v-if="document.downloadUrl" as-child size="sm" variant="outline">
                                    <a :href="document.downloadUrl">
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
