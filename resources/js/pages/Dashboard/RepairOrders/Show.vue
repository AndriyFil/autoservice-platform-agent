<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import RepairOrderDocumentsTab from '@/components/repair-orders/RepairOrderDocumentsTab.vue';
import RepairOrderEstimateApprovalSettings from '@/components/repair-orders/RepairOrderEstimateApprovalSettings.vue';
import RepairOrderEstimatesTab from '@/components/repair-orders/RepairOrderEstimatesTab.vue';
import RepairOrderLinesTab from '@/components/repair-orders/RepairOrderLinesTab.vue';
import RepairOrderOverviewTab from '@/components/repair-orders/RepairOrderOverviewTab.vue';
import RepairOrderStatusActions from '@/components/repair-orders/RepairOrderStatusActions.vue';
import RepairOrderStatusDropdown from '@/components/repair-orders/RepairOrderStatusDropdown.vue';
import RepairOrderTimelineTab from '@/components/repair-orders/RepairOrderTimelineTab.vue';
import type { RepairOrderShowProps } from '@/components/repair-orders/types';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<RepairOrderShowProps>();
const { t } = useTranslations();

type RepairOrderTab = 'overview' | 'lines' | 'estimates' | 'documents' | 'timeline';

const activeTab = ref<RepairOrderTab>('overview');

const tabs = computed(() => [
    { value: 'overview' as const, label: t('repair_orders.tabs.overview') },
    { value: 'lines' as const, label: t('repair_orders.tabs.lines') },
    { value: 'estimates' as const, label: t('repair_orders.tabs.estimates') },
    { value: 'documents' as const, label: t('repair_orders.tabs.documents') },
    { value: 'timeline' as const, label: t('repair_orders.tabs.timeline') },
]);

const latestEstimate = computed(() => props.repairOrder.estimates[0] ?? null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('repair_orders.navigation.repair_orders'),
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
                    <Link
                        :href="route('dashboard.repair-orders.index')"
                        class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="size-4" />
                        {{ t('repair_orders.navigation.repair_orders') }}
                    </Link>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                        <h1 class="text-xl font-semibold text-foreground">{{ repairOrder.customer.name }}</h1>
                    </div>
                </div>

                <div class="flex flex-col items-start gap-2 sm:items-end">
                    <RepairOrderStatusDropdown
                        :repair-order-id="repairOrder.id"
                        :status="repairOrder.status"
                        :transitions="repairOrder.availableStatusTransitions"
                        :status-error="errors?.status"
                    />

                    <RepairOrderStatusActions
                        :repair-order-id="repairOrder.id"
                        :actions="repairOrder.statusActions"
                        :latest-estimate="latestEstimate"
                    />
                </div>
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <InputError :message="errors?.repair_order_line" />

            <div class="overflow-x-auto border-b border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex min-w-max gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            activeTab === tab.value
                                ? 'border-foreground text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = tab.value"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <template v-if="activeTab === 'overview'">
                <RepairOrderOverviewTab :repair-order="repairOrder" />
                <RepairOrderEstimateApprovalSettings :repair-order="repairOrder" />
            </template>
            <RepairOrderLinesTab v-else-if="activeTab === 'lines'" :repair-order="repairOrder" />
            <RepairOrderEstimatesTab v-else-if="activeTab === 'estimates'" :repair-order="repairOrder" :status-error="errors?.status" />
            <RepairOrderDocumentsTab v-else-if="activeTab === 'documents'" :documents="repairOrder.documents" />
            <RepairOrderTimelineTab v-else :repair-order="repairOrder" />
        </div>
    </AppLayout>
</template>
