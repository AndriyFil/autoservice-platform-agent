<script setup lang="ts">
import CustomerSearchSelect from '@/components/booking-requests/CustomerSearchSelect.vue';
import { type CustomerSearchResult } from '@/components/booking-requests/types';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Car, Phone, Save, User, Wrench } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Create booking request',
        href: route('booking-requests.create'),
    },
];

const selectedCustomer = ref<CustomerSearchResult | null>(null);

const form = useForm<{
    customer_id: number | null;
    customer_name: string;
    customer_phone: string;
    problem_description: string;
    preferred_date: string;
    vehicle: {
        brand: string;
        model: string;
        license_plate: string;
    };
}>({
    customer_id: null,
    customer_name: '',
    customer_phone: '',
    problem_description: '',
    preferred_date: '',
    vehicle: {
        brand: '',
        model: '',
        license_plate: '',
    },
});

watch(selectedCustomer, (customer) => {
    form.customer_id = customer?.id ?? null;

    if (!customer) {
        return;
    }

    form.customer_name = customer.name;
    form.customer_phone = customer.phone;
    form.clearErrors('customer_id', 'customer_name', 'customer_phone');
});

const nullableValue = (value: string) => {
    const trimmed = value.trim();

    return trimmed.length > 0 ? trimmed : null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        customer_id: selectedCustomer.value?.id ?? null,
        preferred_date: nullableValue(data.preferred_date),
        vehicle: {
            brand: nullableValue(data.vehicle.brand),
            model: nullableValue(data.vehicle.model),
            license_plate: nullableValue(data.vehicle.license_plate),
        },
    })).post(route('booking-requests.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create booking request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link :href="route('dashboard')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="size-4" />
                        Booking requests
                    </Link>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">Dashboard</div>
                        <h1 class="text-xl font-semibold text-foreground">Create booking request</h1>
                    </div>
                </div>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <section class="space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <User class="size-4" />
                        Customer
                    </div>

                    <CustomerSearchSelect v-model="selectedCustomer" />

                    <InputError :message="form.errors.customer_id" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="customer_name">Name</Label>
                            <Input
                                id="customer_name"
                                v-model="form.customer_name"
                                type="text"
                                name="customer_name"
                                autocomplete="name"
                                required
                            />
                            <InputError :message="form.errors.customer_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer_phone">Phone</Label>
                            <div class="relative">
                                <Phone class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="customer_phone"
                                    v-model="form.customer_phone"
                                    class="pl-9"
                                    type="tel"
                                    name="customer_phone"
                                    autocomplete="tel"
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.customer_phone" />
                        </div>
                    </div>
                </section>

                <section class="space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <Wrench class="size-4" />
                        Request
                    </div>

                    <div class="space-y-2">
                        <Label for="problem_description">Problem description</Label>
                        <textarea
                            id="problem_description"
                            v-model="form.problem_description"
                            name="problem_description"
                            rows="5"
                            required
                            class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        />
                        <InputError :message="form.errors.problem_description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="preferred_date">Preferred date</Label>
                        <div class="relative max-w-xs">
                            <CalendarDays class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="preferred_date"
                                v-model="form.preferred_date"
                                class="pl-9"
                                type="date"
                                name="preferred_date"
                            />
                        </div>
                        <InputError :message="form.errors.preferred_date" />
                    </div>
                </section>

                <section class="space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <Car class="size-4" />
                        Vehicle
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="vehicle_brand">Brand</Label>
                            <Input id="vehicle_brand" v-model="form.vehicle.brand" type="text" name="vehicle[brand]" />
                            <InputError :message="form.errors['vehicle.brand']" />
                        </div>

                        <div class="space-y-2">
                            <Label for="vehicle_model">Model</Label>
                            <Input id="vehicle_model" v-model="form.vehicle.model" type="text" name="vehicle[model]" />
                            <InputError :message="form.errors['vehicle.model']" />
                        </div>

                        <div class="space-y-2">
                            <Label for="vehicle_license_plate">License plate</Label>
                            <Input
                                id="vehicle_license_plate"
                                v-model="form.vehicle.license_plate"
                                type="text"
                                name="vehicle[license_plate]"
                            />
                            <InputError :message="form.errors['vehicle.license_plate']" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Button as-child variant="outline">
                        <Link :href="route('dashboard')">Cancel</Link>
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        Create request
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
