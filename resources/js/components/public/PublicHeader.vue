<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardList, LogIn, Menu, UserPlus } from 'lucide-vue-next';
import PublicBrand from './PublicBrand.vue';

withDefaults(
    defineProps<{
        canLogin?: boolean;
        canRegister?: boolean;
        adminLoginUrl?: string;
        adminRegisterUrl?: string;
    }>(),
    {
        canLogin: false,
        canRegister: false,
        adminLoginUrl: '',
        adminRegisterUrl: '',
    },
);
</script>

<template>
    <header class="border-b border-[#dfe4e4]/90 bg-white">
        <div class="mx-auto flex min-h-[4.75rem] w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <Link
                :href="route('home')"
                class="rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-4"
                aria-label="AutoService home"
            >
                <PublicBrand />
            </Link>

            <nav aria-label="Primary" class="hidden items-center gap-1.5 sm:flex">
                <Link
                    :href="route('customer-portal.index')"
                    class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3.5 text-sm font-semibold text-[#40526a] transition hover:bg-[#f6f8f7] hover:text-[#0b1f33] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                >
                    <ClipboardList class="size-4" aria-hidden="true" />
                    My requests
                </Link>
                <a
                    v-if="canLogin"
                    :href="adminLoginUrl"
                    class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3.5 text-sm font-semibold text-[#40526a] transition hover:bg-[#f6f8f7] hover:text-[#0b1f33] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                >
                    <LogIn class="size-4" aria-hidden="true" />
                    Staff login
                </a>
                <a v-if="canRegister" :href="adminRegisterUrl" class="public-button-primary ml-1 focus-visible:ring-[#0e7c86]">
                    <UserPlus class="size-4" aria-hidden="true" />
                    Create workshop account
                </a>
            </nav>

            <details class="group relative sm:hidden">
                <summary
                    class="flex size-11 cursor-pointer list-none items-center justify-center rounded-xl border border-[#dfe4e4] bg-white text-[#0b1f33] transition marker:content-none hover:bg-[#f6f8f7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                    aria-label="Open navigation"
                >
                    <Menu class="size-5" aria-hidden="true" />
                </summary>
                <nav
                    aria-label="Mobile primary"
                    class="absolute right-0 z-30 mt-2 grid min-w-64 gap-1 rounded-2xl border border-[#dfe4e4] bg-white p-2 shadow-[0_18px_48px_-24px_rgba(11,31,51,0.45)]"
                >
                    <Link
                        :href="route('customer-portal.index')"
                        class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold text-[#40526a] hover:bg-[#f3f7f6] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86]"
                    >
                        <ClipboardList class="size-4 text-[#0e7c86]" aria-hidden="true" /> My requests
                    </Link>
                    <a
                        v-if="canLogin"
                        :href="adminLoginUrl"
                        class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold text-[#40526a] hover:bg-[#f3f7f6] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86]"
                    >
                        <LogIn class="size-4 text-[#0e7c86]" aria-hidden="true" /> Staff login
                    </a>
                    <a
                        v-if="canRegister"
                        :href="adminRegisterUrl"
                        class="mt-1 flex min-h-11 items-center gap-3 rounded-xl bg-[#0b1f33] px-3 text-sm font-semibold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86]"
                    >
                        <UserPlus class="size-4" aria-hidden="true" /> Create workshop account
                    </a>
                </nav>
            </details>
        </div>
    </header>
</template>
