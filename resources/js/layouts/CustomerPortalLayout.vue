<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShieldCheck, Wrench } from 'lucide-vue-next';
</script>

<template>
    <main class="min-h-screen bg-[#f7f3ea] text-slate-900">
        <header class="mx-auto flex w-full max-w-md items-center px-5 py-5 sm:px-6">
            <Link :href="route('home')" class="flex items-center gap-3 rounded-md" aria-label="AutoService home">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#2f6471] text-white shadow-sm">
                    <Wrench class="size-5" aria-hidden="true" />
                </span>
                <span class="text-base font-semibold tracking-tight">AutoService</span>
            </Link>
        </header>

        <section class="mx-auto w-full max-w-md px-5 pb-12 pt-8 sm:px-6 sm:pb-16 sm:pt-12">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-[0_18px_60px_-32px_rgba(47,100,113,0.55)] sm:p-8">
                <slot />
            </div>

            <p class="mt-5 flex items-center justify-center gap-2 text-center text-xs leading-5 text-slate-500">
                <ShieldCheck class="size-4 shrink-0 text-[#2f6471]" aria-hidden="true" />
                Secure phone verification
            </p>
        </section>
    </main>
</template>
