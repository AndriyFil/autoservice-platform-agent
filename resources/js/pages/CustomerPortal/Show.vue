<script setup lang="ts">
import CustomerRequestDetail from '@/components/public-portal/CustomerRequestDetail.vue';
import CustomerRequestHistory from '@/components/public-portal/CustomerRequestHistory.vue';
import type { CustomerRequestDetail as CustomerRequestDetailPayload, CustomerRequestSummary } from '@/components/public-portal/types';
import PublicWorkspaceLayout from '@/layouts/PublicWorkspaceLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    request: CustomerRequestDetailPayload;
    recentRequests: CustomerRequestSummary[];
    hasMoreRequests: boolean;
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();
</script>

<template>
    <Head :title="request.title" />

    <PublicWorkspaceLayout :can-login="canLogin" :can-register="canRegister" :admin-login-url="adminLoginUrl" :admin-register-url="adminRegisterUrl">
        <template #history>
            <CustomerRequestHistory :requests="recentRequests" :has-more="hasMoreRequests" :selected-request-id="request.id" />
        </template>

        <CustomerRequestDetail :request="request" />
    </PublicWorkspaceLayout>
</template>
