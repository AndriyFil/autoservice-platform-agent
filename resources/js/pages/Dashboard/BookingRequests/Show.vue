<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Check, X } from 'lucide-vue-next';
import { ref } from 'vue';

type StatusAction = 'confirmed' | 'rejected' | 'cancelled';
type PendingStatusChange = {
    status: StatusAction;
    label: string;
    description: string;
    confirmButtonClass: string;
};

const props = defineProps<{
    activeWorkshop: {
        id: number;
        name: string;
        slug: string;
    };
    bookingRequest: {
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
        updatedAt: string;
    };
    flash?: {
        status?: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: props.bookingRequest.customerName,
        href: route('dashboard.booking-requests.show', { bookingRequest: props.bookingRequest.id }),
    },
];

const form = useForm({
    status: '',
});

const statusDialogOpen = ref(false);
const pendingStatusChange = ref<PendingStatusChange | null>(null);

const canConfirm = () => props.bookingRequest.status.value === 'new';
const canReject = () => props.bookingRequest.status.value === 'new';
const canCancel = () => ['new', 'confirmed'].includes(props.bookingRequest.status.value);

const statusActionDetails = (status: StatusAction) =>
    ({
        confirmed: {
            label: 'Confirm request',
            description: 'This marks the request as confirmed. You can still cancel it later if needed.',
            confirmButtonClass: 'bg-green-600 text-white hover:bg-green-700',
        },
        rejected: {
            label: 'Reject request',
            description: 'This marks the request as rejected. There is no valid transition back from rejected.',
            confirmButtonClass: '',
        },
        cancelled: {
            label: 'Cancel request',
            description: 'This marks the request as cancelled. Use this when the request should no longer continue.',
            confirmButtonClass: 'bg-amber-600 text-white hover:bg-amber-700',
        },
    })[status];

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

const openStatusDialog = (status: StatusAction) => {
    pendingStatusChange.value = {
        status,
        ...statusActionDetails(status),
    };
    statusDialogOpen.value = true;
};

const submitStatus = (status: StatusAction) => {
    form.status = status;

    form.patch(route('dashboard.booking-requests.status', { bookingRequest: props.bookingRequest.id }), {
        preserveScroll: true,
        onFinish: () => form.reset('status'),
    });
};

const submitPendingStatus = () => {
    if (!pendingStatusChange.value) {
        return;
    }

    submitStatus(pendingStatusChange.value.status);
    statusDialogOpen.value = false;
    pendingStatusChange.value = null;
};
</script>

<template>
    <Head :title="bookingRequest.customerName" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link :href="route('dashboard')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="size-4" />
                        Booking requests
                    </Link>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                        <h1 class="text-xl font-semibold text-foreground">{{ bookingRequest.customerName }}</h1>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="canConfirm()"
                        type="button"
                        size="sm"
                        class="bg-green-600 text-white hover:bg-green-700"
                        :disabled="form.processing"
                        @click="openStatusDialog('confirmed')"
                    >
                        <Check class="size-4" />
                        Confirm
                    </Button>

                    <Button
                        v-if="canReject()"
                        type="button"
                        size="sm"
                        variant="destructive"
                        :disabled="form.processing"
                        @click="openStatusDialog('rejected')"
                    >
                        <X class="size-4" />
                        Reject
                    </Button>

                    <Button
                        v-if="canCancel()"
                        type="button"
                        size="sm"
                        class="bg-amber-600 text-white hover:bg-amber-700"
                        :disabled="form.processing"
                        @click="openStatusDialog('cancelled')"
                    >
                        <Ban class="size-4" />
                        Cancel
                    </Button>
                </div>
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <InputError :message="form.errors.status" />

            <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
                        <h2 class="text-base font-semibold text-foreground">Request details</h2>
                        <span class="inline-flex rounded-md border border-sidebar-border/70 px-2 py-1 text-xs font-medium text-foreground dark:border-sidebar-border">
                            {{ bookingRequest.status.label }}
                        </span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Customer</div>
                            <div class="mt-1 text-sm text-foreground">{{ bookingRequest.customerName }}</div>
                            <div class="text-sm text-muted-foreground">{{ bookingRequest.customerPhone }}</div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Preferred date</div>
                            <div class="mt-1 text-sm text-foreground">{{ formatDate(bookingRequest.preferredDate) }}</div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Vehicle</div>
                            <div class="mt-1 text-sm text-foreground">{{ vehicleSummary(bookingRequest.vehicle) }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase text-muted-foreground">Problem</div>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-foreground">{{ bookingRequest.problemDescription }}</p>
                    </div>
                </section>

                <aside class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 class="text-base font-semibold text-foreground">Timeline</h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Created</div>
                            <div class="mt-1 text-foreground">{{ formatDateTime(bookingRequest.createdAt) }}</div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Updated</div>
                            <div class="mt-1 text-foreground">{{ formatDateTime(bookingRequest.updatedAt) }}</div>
                        </div>
                    </div>
                </aside>
            </div>

            <Dialog v-model:open="statusDialogOpen">
                <DialogContent>
                    <DialogHeader class="space-y-3">
                        <DialogTitle>{{ pendingStatusChange?.label }}</DialogTitle>
                        <DialogDescription>
                            {{ pendingStatusChange?.description }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="rounded-md border border-sidebar-border/70 bg-muted/40 px-3 py-2 text-sm text-foreground">
                        {{ bookingRequest.customerName }}
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Keep current status</Button>
                        </DialogClose>

                        <Button
                            type="button"
                            :variant="pendingStatusChange?.status === 'rejected' ? 'destructive' : 'default'"
                            :class="pendingStatusChange?.confirmButtonClass"
                            :disabled="form.processing"
                            @click="submitPendingStatus"
                        >
                            {{ pendingStatusChange?.label }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
