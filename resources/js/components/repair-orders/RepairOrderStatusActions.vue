<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import type { RepairOrderEstimate, RepairOrderStatusActions } from './types';
import { formatCents, formatDateTime } from './utils';

const props = defineProps<{
    repairOrderId: number;
    actions: RepairOrderStatusActions;
    latestEstimate?: RepairOrderEstimate | null;
    statusError?: string;
}>();
const { t } = useTranslations();

const estimateForm = useForm({});

const anyProcessing = () => estimateForm.processing;

const submitEstimate = () => {
    estimateForm.post(route('dashboard.repair-orders.estimate', { repairOrder: props.repairOrderId }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap gap-2">
            <Button v-if="actions.canGenerateEstimate" type="button" size="sm" :disabled="anyProcessing()" @click="submitEstimate">
                <FileText class="size-4" />
                {{ t(actions.hasEstimate ? 'repair_orders.actions.create_new_estimate_pdf' : 'repair_orders.actions.create_estimate_pdf') }}
            </Button>

            <div v-if="latestEstimate" class="rounded-md border border-sidebar-border/70 px-3 py-1.5 text-sm dark:border-sidebar-border">
                <span class="font-medium text-foreground">{{ t('repair_orders.sections.latest_estimate') }}</span>
                <a
                    v-if="latestEstimate.document?.viewUrl"
                    :href="latestEstimate.document.viewUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ml-2 text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                >
                    v{{ latestEstimate.version }} · {{ latestEstimate.status.label }} · {{ formatCents(latestEstimate.totalCents) }}
                    <span v-if="latestEstimate.generatedAt">· {{ formatDateTime(latestEstimate.generatedAt) }}</span>
                </a>
                <span v-else class="ml-2 text-muted-foreground">
                    v{{ latestEstimate.version }} · {{ latestEstimate.status.label }} · {{ formatCents(latestEstimate.totalCents) }}
                    <span v-if="latestEstimate.generatedAt">· {{ formatDateTime(latestEstimate.generatedAt) }}</span>
                </span>
            </div>
            <div
                v-else-if="actions.canGenerateEstimate"
                class="rounded-md border border-sidebar-border/70 px-3 py-1.5 text-sm text-muted-foreground dark:border-sidebar-border"
            >
                {{ t('repair_orders.messages.no_estimate_summary') }}
            </div>
        </div>

        <InputError :message="statusError" />
    </div>
</template>
