<script setup lang="ts">
import PublicIntakeLink from '@/components/workshop/PublicIntakeLink.vue';
import WorkshopSettingsForm from '@/components/workshop/WorkshopSettingsForm.vue';
import WorkshopStaffManagement from '@/components/workshop/WorkshopStaffManagement.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import type { WorkshopSettingsPageProps } from './type';

defineProps<WorkshopSettingsPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workshop settings',
        href: route('dashboard.workshop.settings.show'),
    },
];
</script>

<template>
    <Head title="Workshop settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <div class="text-sm font-medium text-muted-foreground">{{ activeWorkshop.name }}</div>
                <h1 class="text-xl font-semibold text-foreground">Workshop settings</h1>
            </div>

            <div v-if="flash?.status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.status }}
            </div>

            <WorkshopSettingsForm :workshop="workshop" />
            <PublicIntakeLink :workshop="workshop" />
            <WorkshopStaffManagement :staff-members="staffMembers" :role-options="roleOptions" />
        </div>
    </AppLayout>
</template>
