<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import type { RepairOrderDetails } from './types';

const props = defineProps<{
    repairOrder: RepairOrderDetails;
}>();
const { t } = useTranslations();

const form = useForm({
    requires_estimate_approval: props.repairOrder.requiresEstimateApproval,
});

watch(
    () => props.repairOrder.requiresEstimateApproval,
    (requiresEstimateApproval) => {
        form.requires_estimate_approval = requiresEstimateApproval;
    },
);

const submit = (value: boolean | 'indeterminate') => {
    if (!props.repairOrder.canUpdateEstimateApprovalRequirement || form.processing) {
        return;
    }

    form.requires_estimate_approval = value === true;

    form.patch(route('dashboard.repair-orders.estimate-approval-requirement.update', { repairOrder: props.repairOrder.id }), {
        preserveScroll: true,
        onError: () => {
            form.requires_estimate_approval = props.repairOrder.requiresEstimateApproval;
        },
    });
};
</script>

<template>
    <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div>
            <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.sections.order_settings') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('repair_orders.messages.estimate_approval_behavior') }}</p>
        </div>

        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <Checkbox
                    id="show_requires_estimate_approval"
                    :model-value="form.requires_estimate_approval"
                    :disabled="!repairOrder.canUpdateEstimateApprovalRequirement || form.processing"
                    @update:model-value="submit"
                />

                <div class="grid gap-1.5">
                    <Label for="show_requires_estimate_approval">{{ t('repair_orders.fields.requires_estimate_approval') }}</Label>
                    <p class="text-sm text-muted-foreground">
                        {{ t('repair_orders.messages.requires_estimate_approval_help') }}
                    </p>
                    <p v-if="!repairOrder.canUpdateEstimateApprovalRequirement" class="text-sm text-muted-foreground">
                        {{ t('repair_orders.messages.estimate_approval_locked') }}
                    </p>
                    <p v-else-if="form.processing" class="text-sm text-muted-foreground">{{ t('repair_orders.messages.saving') }}</p>
                    <p v-else-if="form.recentlySuccessful" class="text-sm text-muted-foreground">{{ t('repair_orders.messages.saved') }}</p>
                    <InputError :message="form.errors.requires_estimate_approval" />
                </div>
            </div>
        </div>
    </section>
</template>
