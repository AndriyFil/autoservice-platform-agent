<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Ban, ClipboardList, Phone, UserRound, Wrench, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type BookingRequestStatus = 'new' | 'confirmed' | 'rejected' | 'cancelled';
type StatusAction = 'rejected' | 'cancelled';
type RepairOrderStatus = 'draft' | 'in_progress' | 'completed' | 'cancelled';
type TriageTab = 'overview' | 'customer' | 'vehicle';

type StatusOption<T extends string = string> = {
    value: T;
    label: string;
};

type PendingStatusChange = {
    status: StatusAction;
    label: string;
    description: string;
    confirmButtonClass: string;
};

type VehicleSummary = {
    id?: number;
    brand: string | null;
    model: string | null;
    year: number | null;
    licensePlate: string | null;
};

const props = defineProps<{
    activeWorkshop: {
        id: number;
        name: string;
        slug: string;
    };
    bookingRequest: {
        id: number;
        identifier: string;
        customerName: string | null;
        customerPhone: string;
        customerPhoneNormalized: string | null;
        problemDescription: string;
        originalMessage: string | null;
        preferredDate: string | null;
        status: StatusOption<BookingRequestStatus>;
        vehicle: Omit<VehicleSummary, 'id'> | null;
        extractedData: {
            phone: string;
            customerName: string | null;
            vehicle: string | null;
            preferredDate: string | null;
            summary: string | null;
        };
        createdAt: string;
        updatedAt: string;
    };
    matchedCustomer: {
        id: number;
        name: string | null;
        phone: string;
        showUrl: string;
    } | null;
    matchedCustomerVehicles: VehicleSummary[];
    linkedRepairOrder: {
        id: number;
        status: StatusOption<RepairOrderStatus>;
        showUrl: string;
    } | null;
    canCreateRepairOrder: boolean;
    availableStatusTransitions: StatusOption<StatusAction>[];
    customerCreationNotice: string | null;
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
        title: props.bookingRequest.identifier,
        href: route('dashboard.booking-requests.show', { bookingRequest: props.bookingRequest.id }),
    },
];

const form = useForm({
    status: '',
});

const statusDialogOpen = ref(false);
const pendingStatusChange = ref<PendingStatusChange | null>(null);
const activeTab = ref<TriageTab>('overview');

const triageTabs: Array<{ value: TriageTab; label: string }> = [
    {
        value: 'overview',
        label: 'Overview',
    },
    {
        value: 'customer',
        label: 'Customer',
    },
    {
        value: 'vehicle',
        label: 'Vehicle',
    },
];

const repairOrderCreateUrl = computed(() =>
    route('dashboard.repair-orders.create', {
        booking_request: props.bookingRequest.id,
    }),
);

const visibleSecondaryTransitions = computed(() => props.availableStatusTransitions);

const canOpenCreateFlow = computed(() => props.canCreateRepairOrder);

const statusActionDetails = (status: StatusAction) =>
    ({
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

const vehicleSummary = (vehicle: VehicleSummary | Omit<VehicleSummary, 'id'> | null): string => {
    if (!vehicle) {
        return 'No vehicle details extracted';
    }

    const parts = [vehicle.brand, vehicle.model, vehicle.year, vehicle.licensePlate].filter(Boolean);

    return parts.length > 0 ? parts.join(' ') : 'Vehicle details incomplete';
};

const extractedRows = computed<[string, string][]>(() => [
    ['Phone', props.bookingRequest.extractedData.phone],
    ['Submitted name', props.bookingRequest.extractedData.customerName ?? 'Not provided'],
    ['Vehicle mentioned by customer', props.bookingRequest.extractedData.vehicle ?? 'Not found in message'],
    [
        'Preferred date/time',
        props.bookingRequest.extractedData.preferredDate ? formatDate(props.bookingRequest.extractedData.preferredDate) : 'Not provided',
    ],
    ['Customer concern', props.bookingRequest.extractedData.summary ?? 'Not found in message'],
]);

const secondaryActionLabel = (transition: StatusOption<StatusAction>): string => {
    if (transition.value === 'cancelled') {
        return 'Cancel request';
    }

    if (transition.value === 'rejected') {
        return 'Reject request';
    }

    return transition.label;
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
    <Head :title="`Booking request ${bookingRequest.identifier}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-5 p-4">
            <div class="border-b border-sidebar-border/70 pb-5 dark:border-sidebar-border">
                <div class="space-y-3">
                    <Link :href="route('dashboard')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="size-4" />
                        Booking requests
                    </Link>

                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-semibold text-foreground">Booking request {{ bookingRequest.identifier }}</h1>
                            <span
                                class="inline-flex rounded-md border border-sidebar-border/70 px-2 py-1 text-xs font-medium text-foreground dark:border-sidebar-border"
                            >
                                {{ bookingRequest.status.label }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            <span>{{ activeWorkshop.name }}</span>
                            <span>Created {{ formatDateTime(bookingRequest.createdAt) }}</span>
                            <span v-if="bookingRequest.customerName">{{ bookingRequest.customerName }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <InputError :message="form.errors.status" />

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(22rem,0.8fr)]">
                <main class="space-y-5">
                    <section class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div class="flex flex-wrap gap-2 border-b border-sidebar-border/70 pb-3 dark:border-sidebar-border" role="tablist">
                            <button
                                v-for="tab in triageTabs"
                                :key="tab.value"
                                type="button"
                                role="tab"
                                :aria-selected="activeTab === tab.value"
                                class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                                :class="
                                    activeTab === tab.value
                                        ? 'bg-foreground text-background'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                "
                                @click="activeTab = tab.value"
                            >
                                {{ tab.label }}
                            </button>
                        </div>

                        <div v-if="activeTab === 'overview'" class="space-y-4 pt-4" role="tabpanel">
                            <div>
                                <div class="mb-3">
                                    <h2 class="text-base font-semibold text-foreground">Original customer message</h2>
                                    <p class="text-sm text-muted-foreground">Original message from the customer.</p>
                                </div>

                                <p class="whitespace-pre-line rounded-md bg-muted/50 p-4 text-lg leading-8 text-foreground">
                                    {{ bookingRequest.originalMessage ?? bookingRequest.problemDescription }}
                                </p>
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-foreground">Request details</h2>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div
                                        v-for="[label, value] in extractedRows"
                                        :key="label"
                                        class="rounded-md border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                                    >
                                        <div class="text-xs font-medium uppercase text-muted-foreground">{{ label }}</div>
                                        <div class="mt-1 whitespace-pre-line text-sm text-foreground">{{ value }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'customer'" class="pt-4" role="tabpanel">
                            <div class="mb-3 flex items-center gap-2">
                                <UserRound class="size-4 text-muted-foreground" />
                                <h2 class="text-base font-semibold text-foreground">Customer</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs font-medium uppercase text-muted-foreground">Submitted phone</div>
                                        <div class="mt-1 flex items-center gap-2 text-sm text-foreground">
                                            <Phone class="size-4 text-muted-foreground" />
                                            {{ bookingRequest.customerPhone }}
                                        </div>
                                        <div v-if="bookingRequest.customerPhoneNormalized" class="mt-1 text-xs text-muted-foreground">
                                            Normalized: {{ bookingRequest.customerPhoneNormalized }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-xs font-medium uppercase text-muted-foreground">Submitted name</div>
                                        <div class="mt-1 text-sm text-foreground">{{ bookingRequest.customerName ?? 'Not provided' }}</div>
                                    </div>
                                </div>

                                <div class="rounded-md border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                                    <div class="text-xs font-medium uppercase text-muted-foreground">Matched customer record</div>
                                    <div v-if="matchedCustomer" class="mt-2 space-y-1">
                                        <Link :href="matchedCustomer.showUrl" class="font-medium text-foreground underline-offset-4 hover:underline">
                                            {{ matchedCustomer.name ?? 'Unnamed customer' }}
                                        </Link>
                                        <div class="text-sm text-muted-foreground">{{ matchedCustomer.phone }}</div>
                                    </div>
                                    <p v-else class="mt-2 text-sm leading-6 text-muted-foreground">{{ customerCreationNotice }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="pt-4" role="tabpanel">
                            <div class="mb-3 flex items-center gap-2">
                                <Wrench class="size-4 text-muted-foreground" />
                                <h2 class="text-base font-semibold text-foreground">Vehicle context</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <div class="text-xs font-medium uppercase text-muted-foreground">Vehicle mentioned by customer</div>
                                    <div class="mt-1 text-sm text-foreground">
                                        {{ bookingRequest.extractedData.vehicle ?? 'No vehicle details found in message.' }}
                                    </div>
                                    <p class="mt-2 text-sm text-muted-foreground">
                                        Select an existing vehicle or add one while creating the repair order.
                                    </p>
                                </div>

                                <div>
                                    <div class="text-xs font-medium uppercase text-muted-foreground">Known vehicles</div>
                                    <div
                                        v-if="matchedCustomerVehicles.length"
                                        class="mt-2 divide-y divide-sidebar-border/70 rounded-md border dark:divide-sidebar-border"
                                    >
                                        <div v-for="vehicle in matchedCustomerVehicles" :key="vehicle.id" class="px-3 py-2 text-sm text-foreground">
                                            {{ vehicleSummary(vehicle) }}
                                        </div>
                                    </div>
                                    <div v-else class="mt-1 text-sm text-muted-foreground">No known vehicles for this customer.</div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="space-y-5">
                    <section class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 class="text-base font-semibold text-foreground">Next action</h2>

                        <div v-if="linkedRepairOrder" class="mt-4 space-y-3">
                            <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900">
                                This request is linked to repair order #{{ linkedRepairOrder.id }}.
                            </div>
                            <Button as-child class="w-full">
                                <Link :href="linkedRepairOrder.showUrl">
                                    <ClipboardList class="size-4" />
                                    Open repair order #{{ linkedRepairOrder.id }}
                                </Link>
                            </Button>
                        </div>

                        <div v-else-if="canOpenCreateFlow" class="mt-4">
                            <Button as-child class="w-full">
                                <Link :href="repairOrderCreateUrl">
                                    <Wrench class="size-4" />
                                    Create repair order
                                </Link>
                            </Button>
                        </div>

                        <p v-else class="mt-4 text-sm text-muted-foreground">This request cannot be converted from its current state.</p>
                    </section>

                    <section class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 class="text-base font-semibold text-foreground">Request timeline</h2>
                        <div class="mt-4 space-y-3 text-sm">
                            <div>
                                <div class="text-xs font-medium uppercase text-muted-foreground">Created</div>
                                <div class="mt-1 text-foreground">{{ formatDateTime(bookingRequest.createdAt) }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium uppercase text-muted-foreground">Updated</div>
                                <div class="mt-1 text-foreground">{{ formatDateTime(bookingRequest.updatedAt) }}</div>
                            </div>
                            <div v-if="bookingRequest.preferredDate">
                                <div class="text-xs font-medium uppercase text-muted-foreground">Preferred date/time</div>
                                <div class="mt-1 text-foreground">{{ formatDate(bookingRequest.preferredDate) }}</div>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="visibleSecondaryTransitions.length"
                        class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h2 class="text-base font-semibold text-foreground">Secondary actions</h2>
                        <div class="mt-4 flex flex-col gap-2">
                            <Button
                                v-for="transition in visibleSecondaryTransitions"
                                :key="transition.value"
                                type="button"
                                variant="outline"
                                class="justify-start"
                                :disabled="form.processing"
                                @click="openStatusDialog(transition.value)"
                            >
                                <Ban v-if="transition.value === 'cancelled'" class="size-4" />
                                <X v-else class="size-4" />
                                {{ secondaryActionLabel(transition) }}
                            </Button>
                        </div>
                    </section>
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

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Keep request unchanged</Button>
                        </DialogClose>
                        <Button
                            type="button"
                            :class="pendingStatusChange?.confirmButtonClass"
                            :disabled="form.processing"
                            @click="submitPendingStatus"
                        >
                            <Ban v-if="pendingStatusChange?.status === 'cancelled'" class="size-4" />
                            <X v-else class="size-4" />
                            {{ pendingStatusChange?.label }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
