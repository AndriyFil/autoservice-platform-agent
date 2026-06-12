<script setup lang="ts">
import CustomerBookingRequests from '@/components/customers/CustomerBookingRequests.vue';
import CustomerVehicles from '@/components/customers/CustomerVehicles.vue';
import type { CustomerShowProps } from '@/components/customers/types';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps<CustomerShowProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
    {
        title: props.customer.name,
        href: route('customers.show', { customer: props.customer.id }),
    },
];
</script>

<template>
    <Head :title="customer.name" />

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
                        <h1 class="text-xl font-semibold text-foreground">{{ customer.name }}</h1>
                        <div class="text-sm text-muted-foreground">{{ customer.phone }}</div>
                    </div>
                </div>

                <Button as-child size="sm" variant="outline">
                    <Link :href="route('customers.index')">Back to customers</Link>
                </Button>
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                <CustomerBookingRequests :booking-requests="customer.bookingRequests" />
                <CustomerVehicles :vehicles="customer.vehicles" />
            </div>
        </div>
    </AppLayout>
</template>
