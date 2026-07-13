<script setup lang="ts">
import { Button } from '@/components/ui/button';
import CustomerPortalLayout from '@/layouts/CustomerPortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Home } from 'lucide-vue-next';
</script>

<template>
    <Head title="Your requests" />

    <CustomerPortalLayout>
        <div class="flex size-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
            <CheckCircle2 class="size-5" aria-hidden="true" />
        </div>

        <div class="mt-6">
            <p class="text-sm font-medium text-emerald-700">Phone verified</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Your requests</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Your phone number has been verified. Request history is not available yet.</p>
        </div>

        <div class="mt-7 rounded-lg border border-slate-200 bg-slate-50 px-4 py-4">
            <p class="text-sm leading-6 text-slate-600">
                This temporary page confirms that secure portal access is working. A service advisor will still contact you directly about your
                requests.
            </p>
        </div>

        <Button as-child variant="outline" class="mt-7 min-h-11 w-full border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
            <Link :href="route('home')">
                <Home class="size-4" aria-hidden="true" />
                Return to AutoService
            </Link>
        </Button>
    </CustomerPortalLayout>
</template>
