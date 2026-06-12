<script setup lang="ts">
import BookingRequestStatusBadge from '@/components/dashboard/BookingRequestStatusBadge.vue';
import type { CustomerBookingRequest } from './types';
import { formatDate, formatDateTime } from './utils';

defineProps<{
    bookingRequests: CustomerBookingRequest[];
}>();
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Booking requests</h2>
        </div>

        <div v-if="bookingRequests.length === 0" class="px-4 py-10 text-center text-sm text-muted-foreground">No booking requests yet.</div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Problem</th>
                        <th class="px-4 py-3">Preferred</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="bookingRequest in bookingRequests" :key="bookingRequest.id">
                        <td class="whitespace-nowrap px-4 py-3 align-top">
                            <BookingRequestStatusBadge :status="bookingRequest.status" />
                        </td>
                        <td class="max-w-xl px-4 py-3 align-top text-foreground">
                            {{ bookingRequest.problemDescription }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ formatDate(bookingRequest.preferredDate) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ formatDateTime(bookingRequest.createdAt) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
