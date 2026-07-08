<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import RepairOrderStatusBadge from '@/components/repair-orders/RepairOrderStatusBadge.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useForm } from '@inertiajs/vue3';
import type { RepairOrderStatus, RepairOrderStatusTransition } from './types';

const props = defineProps<{
    repairOrderId: number;
    status: RepairOrderStatus;
    transitions: RepairOrderStatusTransition[];
    statusError?: string;
}>();

const form = useForm({
    status: props.status.value,
});

const submitTransition = (transition: RepairOrderStatusTransition) => {
    if (transition.value === 'cancelled' && !window.confirm('Cancel this repair order?')) {
        return;
    }

    form.status = transition.value;
    form.patch(route('dashboard.repair-orders.status', { repairOrder: props.repairOrderId }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center gap-2">
            <DropdownMenu v-if="transitions.length > 0">
                <DropdownMenuTrigger as-child>
                    <RepairOrderStatusBadge :status="status" interactive :disabled="form.processing" />
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuItem v-for="transition in transitions" :key="transition.value" @click="submitTransition(transition)">
                        {{ transition.label }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <RepairOrderStatusBadge v-else :status="status" />
        </div>

        <InputError :message="statusError || form.errors.status" />
    </div>
</template>
