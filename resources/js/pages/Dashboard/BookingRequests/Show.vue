<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Check, X } from 'lucide-vue-next';

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

const canConfirm = () => props.bookingRequest.status.value === 'new';
const canReject = () => props.bookingRequest.status.value === 'new';
const canCancel = () => ['new', 'confirmed'].includes(props.bookingRequest.status.value);

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

const submitStatus = (status: 'confirmed' | 'rejected' | 'cancelled') => {
    form.status = status;

    form.patch(route('dashboard.booking-requests.status', { bookingRequest: props.bookingRequest.id }), {
        preserveScroll: true,
    });
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
                        :disabled="form.processing"
                        @click="submitStatus('confirmed')"
                    >
                        <Check class="size-4" />
                        Confirm
                    </Button>

                    <Button
                        v-if="canReject()"
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="form.processing"
                        @click="submitStatus('rejected')"
                    >
                        <X class="size-4" />
                        Reject
                    </Button>

                    <Button
                        v-if="canCancel()"
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="form.processing"
                        @click="submitStatus('cancelled')"
                    >
                        <Ban class="size-4" />
                        Cancel
                    </Button>
                </div>
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
        </div>
    </AppLayout>
</template>
