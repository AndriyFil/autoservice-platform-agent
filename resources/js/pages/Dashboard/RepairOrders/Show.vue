<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import RepairOrderLinesSection from '@/components/repair-orders/RepairOrderLinesSection.vue';
import RepairOrderStatusBadge from '@/components/repair-orders/RepairOrderStatusBadge.vue';
import RepairOrderStatusActions from '@/components/repair-orders/RepairOrderStatusActions.vue';
import RepairOrderTotalsSummary from '@/components/repair-orders/RepairOrderTotalsSummary.vue';
import type { RepairOrderShowProps } from '@/components/repair-orders/types';
import { formatDate, formatDateTime, vehicleSummary } from '@/components/repair-orders/utils';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList } from 'lucide-vue-next';

const props = defineProps<RepairOrderShowProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Repair orders',
        href: route('dashboard.repair-orders.index'),
    },
    {
        title: props.repairOrder.customer.name,
        href: route('dashboard.repair-orders.show', { repairOrder: props.repairOrder.id }),
    },
];

</script>

<template>
    <Head :title="repairOrder.customer.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <Link :href="route('dashboard.repair-orders.index')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="size-4" />
                        Repair orders
                    </Link>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                        <h1 class="text-xl font-semibold text-foreground">{{ repairOrder.customer.name }}</h1>
                    </div>
                </div>

                <RepairOrderStatusActions
                    :repair-order-id="repairOrder.id"
                    :actions="repairOrder.statusActions"
                    :status-error="errors?.status"
                />
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <InputError :message="errors?.repair_order_line" />

            <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
                        <h2 class="text-base font-semibold text-foreground">Work document</h2>
                        <RepairOrderStatusBadge :status="repairOrder.status" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Customer</div>
                            <div class="mt-1 text-sm text-foreground">{{ repairOrder.customer.name }}</div>
                            <div class="text-sm text-muted-foreground">{{ repairOrder.customer.phone }}</div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Vehicle</div>
                            <div class="mt-1 text-sm text-foreground">{{ vehicleSummary(repairOrder.vehicle) }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase text-muted-foreground">Problem</div>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-foreground">{{ repairOrder.problemDescription }}</p>
                    </div>
                </section>

                <aside class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 class="text-base font-semibold text-foreground">Timeline</h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Opened</div>
                            <div class="mt-1 text-foreground">{{ formatDateTime(repairOrder.openedAt) }}</div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Closed</div>
                            <div class="mt-1 text-foreground">{{ formatDateTime(repairOrder.closedAt) }}</div>
                        </div>

                        <div v-if="repairOrder.bookingRequest">
                            <div class="text-xs font-medium uppercase text-muted-foreground">Source request</div>
                            <Button as-child size="sm" variant="outline" class="mt-1">
                                <Link :href="route('dashboard.booking-requests.show', { bookingRequest: repairOrder.bookingRequest.id })">
                                    <ClipboardList class="size-4" />
                                    View booking request
                                </Link>
                            </Button>
                        </div>

                        <div v-if="repairOrder.bookingRequest?.originalMessage">
                            <div class="text-xs font-medium uppercase text-muted-foreground">Original message</div>
                            <p class="mt-1 whitespace-pre-line text-foreground">{{ repairOrder.bookingRequest.originalMessage }}</p>
                        </div>

                        <div v-if="repairOrder.bookingRequest">
                            <div class="text-xs font-medium uppercase text-muted-foreground">Preferred date</div>
                            <div class="mt-1 text-foreground">{{ formatDate(repairOrder.bookingRequest.preferredDate) }}</div>
                        </div>

                        <div v-else>
                            <div class="text-xs font-medium uppercase text-muted-foreground">Source</div>
                            <div class="mt-1 text-foreground">Manual repair order</div>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                <RepairOrderLinesSection
                    :repair-order-id="repairOrder.id"
                    :lines="repairOrder.lines"
                    :available-line-types="repairOrder.availableLineTypes"
                />

                <RepairOrderTotalsSummary :totals="repairOrder.estimateTotals" />
            </div>
        </div>
    </AppLayout>
</template>
