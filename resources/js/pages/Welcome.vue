<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, ClipboardList, Wrench } from 'lucide-vue-next';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="AutoService" />

    <main class="min-h-screen bg-[#f7f3ea] text-slate-900">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between px-5 py-5 sm:px-6">
            <Link :href="route('home')" class="flex items-center gap-3" aria-label="AutoService home">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#2f6471] text-white shadow-sm">
                    <Wrench class="size-5" aria-hidden="true" />
                </span>
                <span class="text-base font-semibold tracking-tight">AutoService</span>
            </Link>

            <nav v-if="canLogin" class="flex items-center gap-2 text-sm">
                <Link
                    :href="route('login')"
                    class="rounded-md px-3 py-2 font-medium text-slate-600 transition hover:bg-white/60 hover:text-slate-950"
                >
                    Login
                </Link>

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="rounded-md border border-slate-300 bg-white/50 px-3 py-2 font-medium text-slate-700 shadow-sm transition hover:border-[#2f6471]/40 hover:bg-white hover:text-slate-950"
                >
                    Register
                </Link>
            </nav>
        </header>

        <section class="mx-auto grid w-full max-w-5xl gap-10 px-5 pb-16 pt-12 sm:px-6 md:grid-cols-[1.2fr_0.8fr] md:items-center md:pb-24 md:pt-20">
            <div>
                <p class="text-sm font-medium text-[#2f6471]">Workshop intake SaaS</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-5xl">
                    Chat-first service requests for every workshop.
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                    AutoService helps workshops collect customer requests from their own public page and review
                    new intake requests in a tenant-scoped dashboard.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex min-h-11 items-center justify-center rounded-md bg-[#2f6471] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#285864]"
                    >
                        Create workshop account
                    </Link>
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white/60 px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-[#2f6471]/40 hover:bg-white"
                    >
                        Staff login
                    </Link>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-[0_18px_60px_-32px_rgba(47,100,113,0.55)]">
                <div class="flex items-start gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#2f6471]/10 text-[#2f6471]">
                        <Building2 class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Tenant public pages</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Each workshop receives requests through its own public URL, so intake belongs to the
                            right workshop from the first message.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-start gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#2f6471]/10 text-[#2f6471]">
                        <ClipboardList class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Staff review first</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Requests preserve the customer's words and wait for staff confirmation before visit time,
                            pricing, or repair decisions.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
