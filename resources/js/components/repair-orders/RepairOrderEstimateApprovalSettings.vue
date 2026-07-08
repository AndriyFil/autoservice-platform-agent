<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import type { RepairOrderDetails } from './types';

const props = defineProps<{
    repairOrder: RepairOrderDetails;
}>();

const form = useForm({
    requires_estimate_approval: props.repairOrder.requiresEstimateApproval,
});

const submit = () => {
    form.patch(route('dashboard.repair-orders.estimate-approval-requirement.update', { repairOrder: props.repairOrder.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div>
            <h2 class="text-base font-semibold text-foreground">Order settings</h2>
            <p class="mt-1 text-sm text-muted-foreground">Estimate approval behavior for this repair order.</p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="flex items-start gap-3">
                <Checkbox
                    id="show_requires_estimate_approval"
                    :model-value="form.requires_estimate_approval"
                    :disabled="!repairOrder.canUpdateEstimateApprovalRequirement || form.processing"
                    @update:model-value="(value) => (form.requires_estimate_approval = value === true)"
                />

                <div class="grid gap-1.5">
                    <Label for="show_requires_estimate_approval">Require customer approval for estimate</Label>
                    <p class="text-sm text-muted-foreground">
                        If enabled, the estimate should be approved by the customer before work starts. Disable for simple jobs confirmed outside the
                        system.
                    </p>
                    <p v-if="!repairOrder.canUpdateEstimateApprovalRequirement" class="text-sm text-muted-foreground">
                        This setting cannot be changed after the repair order is completed or cancelled.
                    </p>
                    <InputError :message="form.errors.requires_estimate_approval" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" size="sm" :disabled="!repairOrder.canUpdateEstimateApprovalRequirement || form.processing">Save</Button>
                <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
            </div>
        </form>
    </section>
</template>
