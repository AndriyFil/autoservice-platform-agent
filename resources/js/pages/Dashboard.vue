<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

defineProps<{
    activeWorkshop: {
        id: number;
        name: string;
        slug: string;
    };
    bookingRequests: {
        id: number;
        customerName: string;
        customerPhone: string;
        problemDescription: string;
        preferredDate: string | null;
        status: {
            value: 'new' | 'confirmed' | 'rejected' | 'cancelled';
            label: string;
        };
        vehicle: {
            brand: string | null;
            model: string | null;
            licensePlate: string | null;
        } | null;
        createdAt: string;
    }[];
}>();

const vehicleSummary = (vehicle: { brand: string | null; model: string | null; licensePlate: string | null } | null): string => {
    if (!vehicle) {
        return 'No vehicle';
    }

    const parts = [vehicle.brand, vehicle.model, vehicle.licensePlate].filter(Boolean);

    return parts.length > 0 ? parts.join(' ') : 'No vehicle';
};

const formatDate = (date: string | null): string => {
    if (!date) {
        return '-';
    }

    const [year, month, day] = date.split('-').map(Number);

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(year, month - 1, day));
};

const formatDateTime = (date: string): string =>
    new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <div class="text-sm font-medium text-muted-foreground">Active workshop</div>
                <h1 class="text-xl font-semibold text-foreground">{{ activeWorkshop.name }}</h1>
            </div>

            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <h2 class="text-base font-semibold text-foreground">Booking requests</h2>
                </div>

                <div v-if="bookingRequests.length === 0" class="px-4 py-10 text-center text-sm text-muted-foreground">
                    No booking requests yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border">
                            <tr>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Problem</th>
                                <th class="px-4 py-3">Preferred</th>
                                <th class="px-4 py-3">Vehicle</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            <tr v-for="bookingRequest in bookingRequests" :key="bookingRequest.id">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-foreground">{{ bookingRequest.customerName }}</div>
                                    <div class="text-muted-foreground">{{ bookingRequest.customerPhone }}</div>
                                </td>
                                <td class="max-w-md px-4 py-3 align-top text-foreground">
                                    {{ bookingRequest.problemDescription }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                                    {{ formatDate(bookingRequest.preferredDate) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                                    {{ vehicleSummary(bookingRequest.vehicle) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 align-top">
                                    <span class="inline-flex rounded-md border border-sidebar-border/70 px-2 py-1 text-xs font-medium text-foreground dark:border-sidebar-border">
                                        {{ bookingRequest.status.label }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                                    {{ formatDateTime(bookingRequest.createdAt) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
