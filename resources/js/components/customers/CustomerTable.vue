<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import CustomerEmptyState from './CustomerEmptyState.vue';
import type { CustomerListItem } from './types';
import { formatDateTime } from './utils';

defineProps<{
    customers: CustomerListItem[];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Customers</h2>
        </div>

        <CustomerEmptyState v-if="customers.length === 0" />

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Vehicles</th>
                        <th class="px-4 py-3">Requests</th>
                        <th class="px-4 py-3">Latest request</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="customer in customers" :key="customer.id">
                        <td class="px-4 py-3 align-top">
                            <Link :href="route('customers.show', { customer: customer.id })" class="font-medium text-foreground underline-offset-4 hover:underline">
                                {{ customer.name }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ customer.phone }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-foreground">
                            {{ customer.vehiclesCount }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-foreground">
                            {{ customer.bookingRequestsCount }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ formatDateTime(customer.latestBookingRequestDate) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                            <Button as-child size="sm" variant="outline">
                                <Link :href="route('customers.show', { customer: customer.id })">
                                    <Eye class="size-4" />
                                    View
                                </Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
