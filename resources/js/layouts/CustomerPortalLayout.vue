<script setup lang="ts">
import PublicWorkspaceLayout from '@/layouts/PublicWorkspaceLayout.vue';
import { ShieldCheck } from 'lucide-vue-next';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();
</script>

<template>
    <PublicWorkspaceLayout :can-login="canLogin" :can-register="canRegister" :admin-login-url="adminLoginUrl" :admin-register-url="adminRegisterUrl">
        <section class="mx-auto w-full max-w-md px-4 pb-12 pt-8 sm:px-6 sm:pb-16 sm:pt-12">
            <div class="public-card rounded-[1.75rem] p-6 sm:p-8">
                <slot />
            </div>

            <p class="mt-6 flex items-center justify-center gap-2 text-center text-xs leading-5 text-[#607086]">
                <ShieldCheck class="size-4 shrink-0 text-[#0e7c86]" aria-hidden="true" />
                Secure phone verification
            </p>
        </section>
    </PublicWorkspaceLayout>
</template>
