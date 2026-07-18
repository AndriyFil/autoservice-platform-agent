<script setup lang="ts">
import { CheckCircle2, LockKeyhole, PhoneCall, ShieldCheck } from 'lucide-vue-next';
</script>

<template>
    <aside aria-label="Request safety" class="space-y-4 lg:sticky lg:top-6 lg:self-start">
        <div class="public-card rounded-[1.5rem] p-5 sm:p-6">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-[#e9f3f2] text-[#0e7c86]">
                <ShieldCheck class="size-5" aria-hidden="true" />
            </span>
            <h2 class="mt-5 text-lg font-bold tracking-[-0.02em] text-[#0b1f33]">Your request stays simple</h2>
            <p class="mt-2 text-sm leading-6 text-[#607086]">Share the issue once. The workshop will confirm the details with you.</p>

            <div class="mt-6 space-y-5 border-t border-[#e6e9e8] pt-5">
                <div class="flex gap-3">
                    <LockKeyhole class="mt-0.5 size-4 shrink-0 text-[#0e7c86]" aria-hidden="true" />
                    <div>
                        <h3 class="text-sm font-semibold text-[#0b1f33]">Only the selected workshop</h3>
                        <p class="mt-1 text-xs leading-5 text-[#607086]">Your request is sent to the workshop you choose.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <PhoneCall class="mt-0.5 size-4 shrink-0 text-[#0e7c86]" aria-hidden="true" />
                    <div>
                        <h3 class="text-sm font-semibold text-[#0b1f33]">A human follows up</h3>
                        <p class="mt-1 text-xs leading-5 text-[#607086]">The workshop will call to confirm what happens next.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-[#0e7c86]" aria-hidden="true" />
                    <div>
                        <h3 class="text-sm font-semibold text-[#0b1f33]">Nothing is sent early</h3>
                        <p class="mt-1 text-xs leading-5 text-[#607086]">You can review and edit every answer before sending.</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>
