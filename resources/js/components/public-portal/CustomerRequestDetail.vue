<script setup lang="ts">
import type { CustomerRequestDetail } from '@/components/public-portal/types';

defineProps<{
    request: CustomerRequestDetail;
}>();

const formatDateTime = (value: string) =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
</script>

<template>
    <article aria-labelledby="request-title" class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-3xl border border-[#dfe4e4] bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="public-kicker">Service request</p>
                    <h1 id="request-title" class="mt-2 break-words text-3xl font-bold tracking-[-0.035em] text-[#0b1f33]">
                        {{ request.title }}
                    </h1>
                    <p class="mt-2 text-sm text-[#607086]">{{ request.workshopName }}</p>
                </div>
                <span class="w-fit shrink-0 rounded-full bg-[#e9f3f2] px-3 py-1.5 text-sm font-semibold text-[#0e7c86]">
                    {{ request.status.label }}
                </span>
            </div>

            <section v-if="request.problemDescription" data-testid="problem-description" class="mt-7 border-t border-[#dfe4e4] pt-6">
                <h2 class="text-sm font-semibold text-[#0b1f33]">Problem description</h2>
                <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-[#43566c]">{{ request.problemDescription }}</p>
            </section>

            <dl
                v-if="request.customerName || Object.keys(request.vehicle).length > 0"
                class="mt-7 grid gap-5 border-t border-[#dfe4e4] pt-6 sm:grid-cols-2"
            >
                <div v-if="request.customerName" data-testid="customer-name">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#607086]">Customer name</dt>
                    <dd class="mt-1.5 text-sm text-[#0b1f33]">{{ request.customerName }}</dd>
                </div>
                <div v-if="Object.keys(request.vehicle).length > 0" data-testid="vehicle-details">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#607086]">Vehicle</dt>
                    <dd class="mt-1.5 space-y-1 text-sm text-[#0b1f33]">
                        <p v-if="request.vehicle.brand || request.vehicle.model">
                            {{ [request.vehicle.brand, request.vehicle.model].filter(Boolean).join(' ') }}
                        </p>
                        <p v-if="request.vehicle.year">{{ request.vehicle.year }}</p>
                        <p v-if="request.vehicle.licensePlate">{{ request.vehicle.licensePlate }}</p>
                    </dd>
                </div>
            </dl>
        </header>

        <section class="mt-6 rounded-3xl border border-[#dfe4e4] bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-lg font-semibold text-[#0b1f33]">Request progress</h2>
            <ol aria-label="Request progress" class="mt-5 space-y-5 border-l-2 border-[#b9d4d1] pl-5">
                <li>
                    <p class="text-sm font-semibold text-[#0b1f33]">Request submitted</p>
                    <time :datetime="request.submittedAt" class="mt-1 block text-sm text-[#607086]">
                        {{ formatDateTime(request.submittedAt) }}
                    </time>
                </li>
                <li>
                    <p class="text-sm font-semibold text-[#0b1f33]">Current status: {{ request.status.label }}</p>
                    <time :datetime="request.updatedAt" class="mt-1 block text-sm text-[#607086]">
                        Last updated {{ formatDateTime(request.updatedAt) }}
                    </time>
                </li>
            </ol>
        </section>
    </article>
</template>
