<script setup lang="ts">
import CustomerTable from '@/components/customers/CustomerTable.vue';
import type { CustomerIndexProps } from '@/components/customers/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
];

defineProps<CustomerIndexProps>();
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                <h1 class="text-xl font-semibold text-foreground">Customers</h1>
            </div>

            <CustomerTable :customers="customers" />
        </div>
    </AppLayout>
</template>
