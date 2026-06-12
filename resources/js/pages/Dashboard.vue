<script setup lang="ts">
import BookingRequestTable from '@/components/dashboard/BookingRequestTable.vue';
import BookingRequestStatusDialog from '@/components/dashboard/modals/BookingRequestStatusDialog.vue';
import type { DashboardProps, PendingStatusChange, StatusAction } from '@/components/dashboard/types';
import { statusActionDetails } from '@/components/dashboard/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

defineProps<DashboardProps>();

const form = useForm({
    status: '',
});

const statusDialogOpen = ref(false);
const pendingStatusChange = ref<PendingStatusChange | null>(null);

const openStatusDialog = (bookingRequestId: number, customerName: string, status: StatusAction) => {
    pendingStatusChange.value = {
        bookingRequestId,
        customerName,
        status,
        ...statusActionDetails(status),
    };
    statusDialogOpen.value = true;
};

const submitStatus = (bookingRequestId: number, status: StatusAction) => {
    form.status = status;

    form.patch(route('dashboard.booking-requests.status', { bookingRequest: bookingRequestId }), {
        preserveScroll: true,
        onFinish: () => form.reset('status'),
    });
};

const submitPendingStatus = () => {
    if (!pendingStatusChange.value) {
        return;
    }

    submitStatus(pendingStatusChange.value.bookingRequestId, pendingStatusChange.value.status);
    statusDialogOpen.value = false;
    pendingStatusChange.value = null;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <div class="text-sm font-medium text-muted-foreground">Active workshop</div>
                <h1 class="text-xl font-semibold text-foreground">{{ activeWorkshop.name }}</h1>
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <div v-if="errors?.status" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ errors.status }}
            </div>

            <BookingRequestTable :booking-requests="bookingRequests" :processing="form.processing" @status-action="openStatusDialog" />

            <BookingRequestStatusDialog
                v-model:open="statusDialogOpen"
                :pending-status-change="pendingStatusChange"
                :processing="form.processing"
                @submit="submitPendingStatus"
            />
        </div>
    </AppLayout>
</template>
