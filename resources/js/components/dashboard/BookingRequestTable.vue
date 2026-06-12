<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Ban, Check, Eye, X } from 'lucide-vue-next';
import BookingRequestEmptyState from './BookingRequestEmptyState.vue';
import BookingRequestStatusBadge from './BookingRequestStatusBadge.vue';
import type { DashboardBookingRequest, StatusAction } from './types';
import { canCancelBookingRequest, canConfirmBookingRequest, canRejectBookingRequest, formatDate, formatDateTime, vehicleSummary } from './utils';

defineProps<{
    bookingRequests: DashboardBookingRequest[];
    processing: boolean;
}>();

defineEmits<{
    statusAction: [bookingRequestId: number, customerName: string, status: StatusAction];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Booking requests</h2>
        </div>

        <BookingRequestEmptyState v-if="bookingRequests.length === 0" />

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
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="bookingRequest in bookingRequests" :key="bookingRequest.id">
                        <td class="px-4 py-3 align-top">
                            <Link
                                :href="route('dashboard.booking-requests.show', { bookingRequest: bookingRequest.id })"
                                class="font-medium text-foreground underline-offset-4 hover:underline"
                            >
                                {{ bookingRequest.customerName }}
                            </Link>
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
                            <BookingRequestStatusBadge :status="bookingRequest.status" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-muted-foreground">
                            {{ formatDateTime(bookingRequest.createdAt) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                            <div class="flex flex-wrap justify-end gap-2">
                                <Button
                                    v-if="canConfirmBookingRequest(bookingRequest.status.value)"
                                    type="button"
                                    size="sm"
                                    class="bg-green-600 text-white hover:bg-green-700"
                                    :disabled="processing"
                                    @click="$emit('statusAction', bookingRequest.id, bookingRequest.customerName, 'confirmed')"
                                >
                                    <Check class="size-4" />
                                    Confirm
                                </Button>

                                <Button
                                    v-if="canRejectBookingRequest(bookingRequest.status.value)"
                                    type="button"
                                    size="sm"
                                    variant="destructive"
                                    :disabled="processing"
                                    @click="$emit('statusAction', bookingRequest.id, bookingRequest.customerName, 'rejected')"
                                >
                                    <X class="size-4" />
                                    Reject
                                </Button>

                                <Button
                                    v-if="canCancelBookingRequest(bookingRequest.status.value)"
                                    type="button"
                                    size="sm"
                                    class="bg-amber-600 text-white hover:bg-amber-700"
                                    :disabled="processing"
                                    @click="$emit('statusAction', bookingRequest.id, bookingRequest.customerName, 'cancelled')"
                                >
                                    <Ban class="size-4" />
                                    Cancel
                                </Button>

                                <Button as-child size="sm" variant="outline">
                                    <Link :href="route('dashboard.booking-requests.show', { bookingRequest: bookingRequest.id })">
                                        <Eye class="size-4" />
                                        View
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
