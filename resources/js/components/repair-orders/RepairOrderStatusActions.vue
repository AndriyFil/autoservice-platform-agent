<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import { Ban, Check, FileText, RefreshCw } from 'lucide-vue-next';
import { computed } from 'vue';
import type { RepairOrderStatusActions } from './types';

const props = defineProps<{
    repairOrderId: number;
    actions: RepairOrderStatusActions;
    statusError?: string;
}>();
const { t } = useTranslations();

const estimateForm = useForm({});
const completeForm = useForm({});
const cancelForm = useForm({});

const anyProcessing = () => estimateForm.processing || completeForm.processing || cancelForm.processing;

const canSubmitEstimate = computed(() => props.actions.canMarkEstimated || props.actions.canRegenerateEstimate);
const estimateLabel = computed(() =>
    props.actions.canRegenerateEstimate ? t('repair_orders.actions.regenerate_estimate_pdf') : t('repair_orders.actions.create_estimate_pdf'),
);

const submitEstimate = () => {
    estimateForm.post(route('dashboard.repair-orders.estimate', { repairOrder: props.repairOrderId }), {
        preserveScroll: true,
    });
};

const submitComplete = () => {
    completeForm.post(route('dashboard.repair-orders.complete', { repairOrder: props.repairOrderId }), {
        preserveScroll: true,
    });
};

const submitCancel = () => {
    cancelForm.post(route('dashboard.repair-orders.cancel', { repairOrder: props.repairOrderId }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap gap-2">
            <Button
                v-if="canSubmitEstimate"
                type="button"
                size="sm"
                :variant="actions.canRegenerateEstimate ? 'outline' : 'default'"
                :disabled="anyProcessing()"
                @click="submitEstimate"
            >
                <RefreshCw v-if="actions.canRegenerateEstimate" class="size-4" />
                <FileText v-else class="size-4" />
                {{ estimateLabel }}
            </Button>

            <Button
                v-if="actions.canComplete"
                type="button"
                size="sm"
                class="bg-green-600 text-white hover:bg-green-700"
                :disabled="anyProcessing()"
                @click="submitComplete"
            >
                <Check class="size-4" />
                {{ t('repair_orders.actions.complete') }}
            </Button>

            <Button
                v-if="actions.canCancel"
                type="button"
                size="sm"
                class="bg-amber-600 text-white hover:bg-amber-700"
                :disabled="anyProcessing()"
                @click="submitCancel"
            >
                <Ban class="size-4" />
                {{ t('repair_orders.actions.cancel') }}
            </Button>
        </div>

        <InputError :message="statusError" />
    </div>
</template>
