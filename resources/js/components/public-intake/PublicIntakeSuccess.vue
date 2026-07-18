<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, ClipboardList, Plus } from 'lucide-vue-next';

defineProps<{ workshopName?: string }>();
</script>

<template>
    <section aria-labelledby="intake-success-title" aria-live="polite" class="public-card p-7 sm:p-12">
        <div class="mx-auto max-w-2xl text-center">
            <span class="mx-auto flex size-16 items-center justify-center rounded-[1.4rem] bg-emerald-50 text-emerald-700">
                <Check class="size-8" stroke-width="2.5" aria-hidden="true" />
            </span>
            <p class="public-kicker mt-6">All set</p>
            <h1 id="intake-success-title" class="mt-2 text-3xl font-bold tracking-[-0.035em] text-[#0b1f33] sm:text-4xl">Request sent</h1>
            <p v-if="workshopName" class="mt-3 text-lg font-semibold text-slate-900">Your request was sent to {{ workshopName }}.</p>
            <p class="mx-auto mt-2 max-w-xl text-base leading-7 text-slate-600">
                The workshop will contact you by phone to confirm the details and arrange what happens next.
            </p>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <Link :href="route('customer-portal.index')" class="public-button-primary min-h-12">
                    <ClipboardList class="size-4" aria-hidden="true" /> My requests
                </Link>
                <Link :href="route('home')" :preserve-state="false" class="public-button-secondary min-h-12">
                    <Plus class="size-4" aria-hidden="true" /> Create another request
                </Link>
            </div>
        </div>
    </section>
</template>
