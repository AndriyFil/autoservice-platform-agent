<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import RepairOrderCustomerSelect from '@/components/repair-orders/RepairOrderCustomerSelect.vue';
import type { RepairOrderFormVehicle } from '@/components/repair-orders/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';
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
    customer_name: props.defaults.customer_name,
    vehicle_id: props.defaults.vehicle_id,
    booking_request_id: props.defaults.booking_request_id,
    problem_description: props.defaults.problem_description,
    new_vehicle: {
        make: '',
        model: '',
        year: '',
        plate: '',
    },
});

const selectedCustomer = computed(() => props.customers.find((customer) => customer.id === Number(form.customer_id)) ?? null);
const availableVehicles = computed(() => selectedCustomer.value?.vehicles ?? []);
const existingBookingCustomer = computed(() => props.sourceBookingRequest?.existingCustomer ?? null);

const vehicleSummary = (vehicle: RepairOrderFormVehicle): string => {
    const parts = [vehicle.brand, vehicle.model, vehicle.year, vehicle.licensePlate].filter(Boolean);

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

const clearSelectedVehicle = () => {
    form.vehicle_id = '';
};
</script>

<template>
    <Head title="New repair order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-2">
                <Link
                    :href="route('dashboard.repair-orders.index')"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                >
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
                    <div class="font-medium">Creating from booking request</div>
                    <div class="mt-1">Booking request phone: {{ sourceBookingRequest.customerPhone }}</div>
                    <div v-if="existingBookingCustomer" class="mt-2">
                        Existing Customer selected:
                        <span class="font-medium">{{ existingBookingCustomer.name ?? 'Unnamed customer' }}</span>
                        <span> - {{ existingBookingCustomer.phone }}</span>
                    </div>
                    <div v-else class="mt-2 font-medium">New customer will be created</div>
                </div>

                <div v-if="sourceBookingRequest && !existingBookingCustomer" class="space-y-2">
                    <Label for="customer_name">Customer name</Label>
                    <Input id="customer_name" v-model="form.customer_name" type="text" placeholder="Optional" />
                    <p class="text-xs text-muted-foreground">Optional for booking-request conversion. Phone comes from the booking request.</p>
                    <InputError :message="form.errors.customer_name" />
                </div>

                <div v-if="sourceBookingRequest && !existingBookingCustomer" class="space-y-2">
                    <Label for="customer_phone">Booking request phone</Label>
                    <Input id="customer_phone" :model-value="defaults.customer_phone" type="text" disabled />
                </div>

                <div v-if="!sourceBookingRequest" class="space-y-2">
                    <Label for="customer_id">Customer</Label>
                    <RepairOrderCustomerSelect
                        id="customer_id"
                        :model-value="form.customer_id"
                        :customers="customers"
                        @update:model-value="selectCustomer"
                    />
                    <InputError :message="form.errors.customer_id" />
                </div>

                <div v-else-if="existingBookingCustomer" class="space-y-2">
                    <Label>Customer</Label>
                    <div class="rounded-md border border-sidebar-border/70 px-3 py-2 text-sm dark:border-sidebar-border">
                        <div class="font-medium text-foreground">{{ existingBookingCustomer.name ?? 'Unnamed customer' }}</div>
                        <div class="text-muted-foreground">{{ existingBookingCustomer.phone }}</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="vehicle_id">Vehicle</Label>
                    <select
                        id="vehicle_id"
                        v-model="form.vehicle_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        :disabled="!sourceBookingRequest && !selectedCustomer"
                    >
                        <option value="">No vehicle selected</option>
                        <option v-for="vehicle in availableVehicles" :key="vehicle.id" :value="vehicle.id">
                            {{ vehicleSummary(vehicle) }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">Vehicle can be added later when intake starts without clear vehicle details.</p>
                    <InputError :message="form.errors.vehicle_id" />
                </div>

                <div class="space-y-3 rounded-md border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                    <div>
                        <div class="text-sm font-medium text-foreground">Add vehicle details</div>
                        <p class="text-xs text-muted-foreground">Leave blank when no vehicle should be attached.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="new_vehicle_make">Brand / make</Label>
                            <Input id="new_vehicle_make" v-model="form.new_vehicle.make" type="text" @input="clearSelectedVehicle" />
                            <InputError :message="form.errors['new_vehicle.make']" />
                        </div>

                        <div class="space-y-2">
                            <Label for="new_vehicle_model">Model</Label>
                            <Input id="new_vehicle_model" v-model="form.new_vehicle.model" type="text" @input="clearSelectedVehicle" />
                            <InputError :message="form.errors['new_vehicle.model']" />
                        </div>

                        <div class="space-y-2">
                            <Label for="new_vehicle_year">Year</Label>
                            <Input
                                id="new_vehicle_year"
                                v-model="form.new_vehicle.year"
                                type="number"
                                min="1886"
                                max="2100"
                                @input="clearSelectedVehicle"
                            />
                            <InputError :message="form.errors['new_vehicle.year']" />
                        </div>

                        <div class="space-y-2">
                            <Label for="new_vehicle_plate">License plate</Label>
                            <Input id="new_vehicle_plate" v-model="form.new_vehicle.plate" type="text" @input="clearSelectedVehicle" />
                            <InputError :message="form.errors['new_vehicle.plate']" />
                        </div>
                    </div>
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
