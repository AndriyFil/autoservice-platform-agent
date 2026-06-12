<script setup lang="ts">
import RepairOrderTable from '@/components/repair-orders/RepairOrderTable.vue';
import type { RepairOrderIndexProps } from '@/components/repair-orders/types';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Repair orders',
        href: '/dashboard/repair-orders',
    },
];

defineProps<RepairOrderIndexProps>();
</script>

<template>
    <Head title="Repair orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                    <h1 class="text-xl font-semibold text-foreground">Repair orders</h1>
                </div>

                <Button as-child size="sm">
                    <Link :href="route('dashboard.repair-orders.create')">
                        <Plus class="size-4" />
                        New repair order
                    </Link>
                </Button>
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <RepairOrderTable :repair-orders="repairOrders" />
        </div>
    </AppLayout>
</template>
