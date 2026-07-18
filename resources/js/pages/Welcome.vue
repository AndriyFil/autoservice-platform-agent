<script setup lang="ts">
import PublicIntakeFlow from '@/components/public-intake/PublicIntakeFlow.vue';
import PublicIntakeSuccess from '@/components/public-intake/PublicIntakeSuccess.vue';
import type { WorkshopOption } from '@/components/public-intake/types';
import CustomerRequestHistory from '@/components/public-portal/CustomerRequestHistory.vue';
import type { CustomerRequestSummary } from '@/components/public-portal/types';
import PublicWorkspaceLayout from '@/layouts/PublicWorkspaceLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    workshops: WorkshopOption[];
    intakeSubmitted?: boolean;
    intakeWorkshopName?: string;
    recentRequests?: CustomerRequestSummary[];
    hasMoreRequests?: boolean;
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();

const intakeExpanded = ref(false);
</script>

<template>
    <Head title="Request car service" />

    <PublicWorkspaceLayout :can-login="canLogin" :can-register="canRegister" :admin-login-url="adminLoginUrl" :admin-register-url="adminRegisterUrl">
        <template v-if="recentRequests && recentRequests.length > 0" #history>
            <CustomerRequestHistory :requests="recentRequests" :has-more="hasMoreRequests ?? false" />
        </template>

        <div
            class="mx-auto flex w-full max-w-4xl px-4 sm:px-6 lg:px-8"
            :class="
                intakeExpanded && !intakeSubmitted
                    ? 'h-[calc(100dvh-4rem)] items-stretch py-0 lg:h-dvh'
                    : 'min-h-[calc(100dvh-4rem)] items-center py-8 lg:min-h-dvh'
            "
        >
            <PublicIntakeSuccess v-if="intakeSubmitted" :workshop-name="intakeWorkshopName" />
            <PublicIntakeFlow v-else :workshops="workshops" @expanded-change="intakeExpanded = $event" />
        </div>
    </PublicWorkspaceLayout>
</template>
