<script setup lang="ts">
import PublicBrand from '@/components/public/PublicBrand.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <main class="public-page flex min-h-svh flex-col">
        <header class="mx-auto flex w-full max-w-md px-5 py-6 sm:px-6 sm:py-8">
            <Link
                :href="route('home')"
                class="rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-4"
                aria-label="AutoService home"
            >
                <PublicBrand />
            </Link>
        </header>

        <section class="mx-auto flex w-full max-w-md flex-1 px-4 pb-12 pt-3 sm:px-6 sm:pb-16 sm:pt-5">
            <div class="public-card w-full self-start rounded-[1.75rem] p-6 sm:p-8">
                <div class="mb-7">
                    <p class="public-kicker">Workshop access</p>
                    <h1 class="mt-2 text-[1.75rem] font-bold leading-tight tracking-[-0.035em] text-[#0b1f33]">{{ title }}</h1>
                    <p class="mt-3 text-[0.95rem] leading-6 text-[#607086]">{{ description }}</p>
                </div>
                <slot />
            </div>
        </section>
    </main>
</template>
