<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import type { RepairOrderStatusActions } from './types';

const props = defineProps<{
    repairOrderId: number;
    actions: RepairOrderStatusActions;
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
            <Button v-if="actions.canMarkEstimated" type="button" size="sm" :disabled="anyProcessing()" @click="submitEstimate">
                <FileText class="size-4" />
                {{ t(actions.hasEstimate ? 'repair_orders.actions.regenerate_estimate_pdf' : 'repair_orders.actions.create_estimate_pdf') }}
            </Button>
        </div>

        <InputError :message="statusError" />
    </div>
</template>
