<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CustomerBookingRequests from '@/components/customers/CustomerBookingRequests.vue';
import CustomerRepairOrders from '@/components/customers/CustomerRepairOrders.vue';
import CustomerVehicles from '@/components/customers/CustomerVehicles.vue';
import type { CustomerShowProps } from '@/components/customers/types';
import { customerDisplayName, formatDateTime } from '@/components/customers/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<CustomerShowProps>();

type CustomerTab = 'profile' | 'vehicles' | 'repairOrders' | 'bookingRequests';

const activeTab = ref<CustomerTab>('profile');

const tabs = computed(() => [
    { value: 'profile' as const, label: 'Profile' },
    { value: 'vehicles' as const, label: `Vehicles (${props.customer.vehicles.length})` },
    { value: 'repairOrders' as const, label: `Repair orders (${props.customer.repairOrders.length})` },
    { value: 'bookingRequests' as const, label: `Booking requests (${props.customer.bookingRequests.length})` },
]);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: route('customers.index'),
    },
    {
        title: customerDisplayName(props.customer.name),
        href: route('customers.show', { customer: props.customer.id }),
    },
];

const profileForm = useForm({
    name: props.customer.name ?? '',
    phone: props.customer.phone,
});

const submitProfile = () => {
    profileForm.patch(route('customers.update', { customer: props.customer.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="customerDisplayName(customer.name)" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link :href="route('customers.index')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="size-4" />
                        Customers
                    </Link>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                        <h1 class="text-xl font-semibold text-foreground">{{ customerDisplayName(customer.name) }}</h1>
                        <div class="text-sm text-muted-foreground">{{ customer.phone }}</div>
                    </div>
                </div>

                <Button as-child size="sm" variant="outline">
                    <Link :href="route('customers.index')">Back to customers</Link>
                </Button>
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <div class="overflow-x-auto border-b border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex min-w-max gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            activeTab === tab.value
                                ? 'border-foreground text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = tab.value"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <section
                v-if="activeTab === 'profile'"
                class="max-w-2xl space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
                    <h2 class="text-base font-semibold text-foreground">Profile</h2>
                </div>

                <form class="space-y-4" @submit.prevent="submitProfile">
                    <div class="space-y-1">
                        <Label for="customer-name">Name</Label>
                        <Input id="customer-name" v-model="profileForm.name" autocomplete="name" />
                        <InputError :message="profileForm.errors.name" />
                    </div>

                    <div class="space-y-1">
                        <Label for="customer-phone">Phone</Label>
                        <Input id="customer-phone" v-model="profileForm.phone" autocomplete="tel" />
                        <InputError :message="profileForm.errors.phone" />
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase text-muted-foreground">Created</div>
                        <div class="mt-1 text-sm text-foreground">{{ formatDateTime(customer.createdAt) }}</div>
                    </div>

                    <Button type="submit" size="sm" :disabled="profileForm.processing">
                        <Save class="size-4" />
                        Save profile
                    </Button>
                </form>
            </section>

            <CustomerVehicles v-else-if="activeTab === 'vehicles'" :customer-id="customer.id" :vehicles="customer.vehicles" />
            <CustomerRepairOrders v-else-if="activeTab === 'repairOrders'" :repair-orders="customer.repairOrders" />
            <CustomerBookingRequests v-else :booking-requests="customer.bookingRequests" />
        </div>
    </AppLayout>
</template>
