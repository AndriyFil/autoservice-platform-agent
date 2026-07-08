<script setup lang="ts">
import CustomerTable from '@/components/customers/CustomerTable.vue';
import type { CustomerIndexProps } from '@/components/customers/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<CustomerIndexProps>();
const search = ref(props.filters.search);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: route('customers.index'),
    },
];

const submitSearch = () => {
    router.get(
        route('customers.index'),
        { search: search.value },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearSearch = () => {
    search.value = '';
    submitSearch();
};
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                <h1 class="text-xl font-semibold text-foreground">Customers</h1>
            </div>

            <form class="flex max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="submitSearch">
                <Input v-model="search" type="search" placeholder="Search by name or phone" />
                <div class="flex gap-2">
                    <Button type="submit" size="sm">
                        <Search class="size-4" />
                        Search
                    </Button>
                    <Button v-if="filters.search" type="button" size="sm" variant="outline" @click="clearSearch">
                        <X class="size-4" />
                        Clear
                    </Button>
                </div>
            </form>

            <CustomerTable :customers="customers" />
        </div>
    </AppLayout>
</template>
