<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import RepairOrderCustomerSelect from '@/components/repair-orders/RepairOrderCustomerSelect.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import type { RepairOrderFormVehicle } from '@/components/repair-orders/types';
import type { RepairOrderCreateProps } from './type';

const props = defineProps<RepairOrderCreateProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Repair orders',
        href: route('dashboard.repair-orders.index'),
    },
    {
        title: 'New repair order',
        href: route('dashboard.repair-orders.create'),
    },
];

const form = useForm({
    customer_id: props.defaults.customer_id,
    vehicle_id: props.defaults.vehicle_id,
    booking_request_id: props.defaults.booking_request_id,
    problem_description: props.defaults.problem_description,
});

const selectedCustomer = computed(() => props.customers.find((customer) => customer.id === Number(form.customer_id)) ?? null);
const availableVehicles = computed(() => selectedCustomer.value?.vehicles ?? []);

const vehicleSummary = (vehicle: RepairOrderFormVehicle): string => {
    const parts = [vehicle.brand, vehicle.model, vehicle.licensePlate].filter(Boolean);

    return parts.length > 0 ? parts.join(' ') : `Vehicle #${vehicle.id}`;
};

const submit = () => {
    form.post(route('dashboard.repair-orders.store'), {
        preserveScroll: true,
    });
};

const selectCustomer = (customerId: string) => {
    form.customer_id = customerId;
    form.vehicle_id = '';
};
</script>

<template>
    <Head title="New repair order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-2">
                <Link :href="route('dashboard.repair-orders.index')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft class="size-4" />
                    Repair orders
                </Link>

                <div>
                    <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                    <h1 class="text-xl font-semibold text-foreground">New repair order</h1>
                </div>
            </div>

            <form class="max-w-3xl space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="submit">
                <InputError :message="errors?.repair_order" />

                <div v-if="sourceBookingRequest" class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <div class="font-medium">Creating from confirmed booking request</div>
                    <div class="mt-1">
                        {{ sourceBookingRequest.customerName }} - {{ sourceBookingRequest.customerPhone }}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="customer_id">Customer</Label>
                    <RepairOrderCustomerSelect
                        id="customer_id"
                        :model-value="form.customer_id"
                        :customers="customers"
                        @update:model-value="selectCustomer"
                    />
                    <InputError :message="form.errors.customer_id" />
                </div>

                <div class="space-y-2">
                    <Label for="vehicle_id">Vehicle</Label>
                    <select
                        id="vehicle_id"
                        v-model="form.vehicle_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        :disabled="!selectedCustomer"
                    >
                        <option value="">No vehicle selected</option>
                        <option v-for="vehicle in availableVehicles" :key="vehicle.id" :value="vehicle.id">
                            {{ vehicleSummary(vehicle) }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">Vehicle can be added later when intake starts without clear vehicle details.</p>
                    <InputError :message="form.errors.vehicle_id" />
                </div>

                <div class="space-y-2">
                    <Label for="problem_description">Problem description</Label>
                    <textarea
                        id="problem_description"
                        v-model="form.problem_description"
                        rows="6"
                        class="flex min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <InputError :message="form.errors.problem_description" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        Open repair order
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
