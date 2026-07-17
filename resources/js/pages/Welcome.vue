<script setup lang="ts">
import PublicIntakeFlow from '@/components/public-intake/PublicIntakeFlow.vue';
import PublicIntakeSuccess from '@/components/public-intake/PublicIntakeSuccess.vue';
import type { WorkshopOption } from '@/components/public-intake/types';
import PublicWorkspaceLayout from '@/layouts/PublicWorkspaceLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    workshops: WorkshopOption[];
    intakeSubmitted?: boolean;
    intakeWorkshopName?: string;
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();
</script>

<template>
    <Head title="Request car service" />

    <PublicWorkspaceLayout :can-login="canLogin" :can-register="canRegister" :admin-login-url="adminLoginUrl" :admin-register-url="adminRegisterUrl">
        <div class="mx-auto flex min-h-dvh w-full max-w-4xl items-center px-4 py-8 sm:px-6 lg:min-h-screen lg:px-8">
            <PublicIntakeSuccess v-if="intakeSubmitted" :workshop-name="intakeWorkshopName" />
            <PublicIntakeFlow v-else :workshops="workshops" />
        </div>
    </PublicWorkspaceLayout>
</template>
