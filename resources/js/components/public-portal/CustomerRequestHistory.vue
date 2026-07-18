<script setup lang="ts">
import type { CustomerRequestSummary } from '@/components/public-portal/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { requestGroup, type CustomerRequestGroup } from './requestHistory';

const props = defineProps<{
    requests: CustomerRequestSummary[];
    hasMore: boolean;
    selectedRequestId?: number;
}>();

const groupOrder: CustomerRequestGroup[] = ['Today', 'Last 7 days', 'Earlier'];

const groupedRequests = computed(() =>
    groupOrder
        .map((label) => ({ label, requests: props.requests.filter((request) => requestGroup(request.submittedAt) === label) }))
        .filter((group) => group.requests.length > 0),
);

const formatDate = (value: string) =>
    new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
</script>

<template>
    <div class="min-h-0 space-y-4" data-testid="customer-request-history">
        <section v-for="group in groupedRequests" :key="group.label" :aria-label="group.label">
            <h2 class="mb-1.5 px-3.5 text-xs font-semibold uppercase tracking-wide text-[#607086]">
                {{ group.label }}
            </h2>
            <ul class="space-y-1">
                <li v-for="request in group.requests" :key="request.id">
                    <Link
                        :href="route('customer-portal.show', request.id)"
                        :aria-current="selectedRequestId === request.id ? 'page' : undefined"
                        class="public-focus block rounded-xl px-3.5 py-2.5 text-[#43566c] transition hover:bg-slate-100"
                        :class="selectedRequestId === request.id ? 'bg-[#e9f3f2] text-[#0b1f33]' : ''"
                    >
                        <span :data-testid="`request-title-${request.id}`" class="block truncate text-sm font-semibold text-[#0b1f33]">
                            {{ request.title }}
                        </span>
                        <span class="mt-1 flex min-w-0 items-center gap-1.5 text-xs text-[#607086]">
                            <span class="shrink-0">{{ request.status.label }}</span>
                            <span aria-hidden="true">·</span>
                            <span class="truncate">{{ request.workshopName }}</span>
                            <span aria-hidden="true">·</span>
                            <time class="shrink-0" :datetime="request.submittedAt">{{ formatDate(request.submittedAt) }}</time>
                        </span>
                    </Link>
                </li>
            </ul>
        </section>

        <Link
            v-if="hasMore"
            :href="route('customer-portal.index')"
            class="public-focus flex min-h-10 items-center rounded-xl px-3.5 text-sm font-semibold text-[#0e7c86] hover:bg-[#e9f3f2]"
        >
            Show all requests
        </Link>
    </div>
</template>
