<script setup lang="ts">
import CustomerRequestHistory from '@/components/public-portal/CustomerRequestHistory.vue';
import type { CustomerRequestSummary, Paginated } from '@/components/public-portal/types';
import PublicWorkspaceLayout from '@/layouts/PublicWorkspaceLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    recentRequests: CustomerRequestSummary[];
    hasMoreRequests: boolean;
    requests: Paginated<CustomerRequestSummary>;
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();

const formatDate = (value: string) =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(value));
</script>

<template>
    <Head title="Your requests" />

    <PublicWorkspaceLayout :can-login="canLogin" :can-register="canRegister" :admin-login-url="adminLoginUrl" :admin-register-url="adminRegisterUrl">
        <template #history>
            <CustomerRequestHistory :requests="recentRequests" :has-more="hasMoreRequests" />
        </template>

        <section aria-labelledby="requests-title" class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 border-b border-[#dfe4e4] pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="public-kicker">Customer portal</p>
                    <h1 id="requests-title" class="mt-2 text-3xl font-bold tracking-[-0.035em] text-[#0b1f33]">Your requests</h1>
                </div>
                <p class="text-sm text-[#607086]">Newest requests appear first.</p>
            </div>

            <div v-if="requests.data.length === 0" class="mt-10 rounded-3xl border border-[#dfe4e4] bg-white p-7 shadow-sm sm:p-9">
                <h2 class="text-xl font-semibold text-[#0b1f33]">No requests yet</h2>
                <p class="mt-2 text-sm leading-6 text-[#607086]">You do not have any service requests yet.</p>
                <Link
                    :href="route('home')"
                    class="public-button-primary mt-6 inline-flex min-h-11 items-center justify-center rounded-xl px-5 text-sm font-semibold"
                >
                    Create a new request
                </Link>
            </div>

            <ul v-else data-testid="request-list" class="mt-6 space-y-3">
                <li v-for="request in requests.data" :key="request.id">
                    <Link
                        :href="route('customer-portal.show', request.id)"
                        class="public-focus block rounded-2xl border border-[#dfe4e4] bg-white p-5 shadow-sm transition hover:border-[#b9d4d1] hover:shadow-md"
                    >
                        <span class="block text-base font-semibold text-[#0b1f33]">{{ request.title }}</span>
                        <span class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-[#607086]">
                            <span>{{ request.status.label }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ request.workshopName }}</span>
                            <span aria-hidden="true">·</span>
                            <time :datetime="request.submittedAt">{{ formatDate(request.submittedAt) }}</time>
                        </span>
                    </Link>
                </li>
            </ul>

            <nav v-if="requests.last_page > 1" aria-label="Request list pages" class="mt-8 flex flex-wrap gap-2">
                <template v-for="link in requests.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :aria-current="link.active ? 'page' : undefined"
                        class="public-focus inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-[#dfe4e4] bg-white px-3 text-sm text-[#43566c]"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        aria-disabled="true"
                        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-[#dfe4e4] px-3 text-sm text-slate-400"
                    >
                        <span v-html="link.label" />
                    </span>
                </template>
            </nav>
        </section>
    </PublicWorkspaceLayout>
</template>
