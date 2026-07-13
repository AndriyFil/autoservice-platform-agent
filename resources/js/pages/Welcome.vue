<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, ClipboardList, Wrench } from 'lucide-vue-next';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();
</script>

<template>
    <Head title="AutoService" />

    <main class="min-h-screen bg-[#f7f3ea] text-slate-900">
        <header class="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 px-5 py-5 sm:px-6">
            <Link :href="route('home')" class="flex items-center gap-3" aria-label="AutoService home">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#2f6471] text-white shadow-sm">
                    <Wrench class="size-5" aria-hidden="true" />
                </span>
                <span class="text-base font-semibold tracking-tight">AutoService</span>
            </Link>

            <nav aria-label="Primary" class="flex flex-wrap items-center justify-end gap-2 text-sm">
                <Link
                    :href="route('customer-portal.index')"
                    class="rounded-md px-3 py-2 font-medium text-slate-600 transition hover:bg-white/60 hover:text-slate-950"
                >
                    My requests
                </Link>

                <template v-if="canLogin">
                    <Link
                        :href="adminLoginUrl"
                        class="rounded-md px-3 py-2 font-medium text-slate-600 transition hover:bg-white/60 hover:text-slate-950"
                    >
                        Staff login
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="adminRegisterUrl"
                        class="rounded-md border border-slate-300 bg-white/50 px-3 py-2 font-medium text-slate-700 shadow-sm transition hover:border-[#2f6471]/40 hover:bg-white hover:text-slate-950"
                    >
                        Create workshop account
                    </Link>
                </template>
            </nav>
        </header>

        <section class="mx-auto grid w-full max-w-5xl gap-10 px-5 pb-16 pt-12 sm:px-6 md:grid-cols-[1.2fr_0.8fr] md:items-center md:pb-24 md:pt-20">
            <div>
                <p class="text-sm font-medium text-[#2f6471]">Workshop intake SaaS</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-5xl">
                    Chat-first service requests for every workshop.
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                    AutoService helps workshops collect customer requests from their own public page and review new intake requests in a tenant-scoped
                    dashboard.
                </p>
                <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                    Need to send a new request? Use the workshop-specific link provided by your workshop.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        v-if="canRegister"
                        :href="adminRegisterUrl"
                        class="inline-flex min-h-11 items-center justify-center rounded-md bg-[#2f6471] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#285864]"
                    >
                        Create workshop account
                    </Link>
                    <Link
                        v-if="canLogin"
                        :href="adminLoginUrl"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white/60 px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-[#2f6471]/40 hover:bg-white"
                    >
                        Staff login
                    </Link>
                </div>

                <aside aria-labelledby="customer-access-preview-title" class="mt-8 max-w-2xl rounded-lg border border-[#2f6471]/20 bg-white/45 p-5">
                    <h2 id="customer-access-preview-title" class="text-xs font-semibold uppercase tracking-wide text-[#2f6471]">
                        Customer access preview
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-700">
                        Verify your phone to securely access customer services. No account or password required.
                    </p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Request history is not available yet.</p>
                    <Link
                        :href="route('customer-portal.index')"
                        class="mt-4 inline-flex min-h-11 items-center justify-center rounded-md border border-[#2f6471]/30 bg-white/70 px-4 text-sm font-semibold text-[#2f6471] shadow-sm transition hover:border-[#2f6471]/50 hover:bg-white"
                    >
                        Verify phone access
                    </Link>
                </aside>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-[0_18px_60px_-32px_rgba(47,100,113,0.55)]">
                <div class="flex items-start gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#2f6471]/10 text-[#2f6471]">
                        <Building2 class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Tenant public pages</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Each workshop receives requests through its own public URL, so intake belongs to the right workshop from the first
                            message.
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
                            Requests preserve the customer's words and wait for staff confirmation before visit time, pricing, or repair decisions.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
