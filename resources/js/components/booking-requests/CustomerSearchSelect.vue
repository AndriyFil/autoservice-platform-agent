<script setup lang="ts">
import { type CustomerSearchResult } from '@/components/booking-requests/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Search, UserCheck, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

defineProps<{
    modelValue: CustomerSearchResult | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [customer: CustomerSearchResult | null];
}>();

const query = ref('');
const results = ref<CustomerSearchResult[]>([]);
const loading = ref(false);
const searchError = ref<string | null>(null);
const hasSearched = ref(false);
const debounceTimer = ref<ReturnType<typeof setTimeout> | null>(null);
let searchSequence = 0;

const normalizedQuery = computed(() => query.value.trim());
const canSearch = computed(() => normalizedQuery.value.length >= 2);

const clearDebounce = () => {
    if (debounceTimer.value) {
        clearTimeout(debounceTimer.value);
        debounceTimer.value = null;
    }
};

const resetSearchState = () => {
    results.value = [];
    loading.value = false;
    searchError.value = null;
    hasSearched.value = false;
};

const searchCustomers = async (term: string) => {
    const currentSequence = ++searchSequence;

    loading.value = true;
    searchError.value = null;
    hasSearched.value = true;

    try {
        const response = await fetch(`${route('booking-requests.customers.search')}?q=${encodeURIComponent(term)}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (currentSequence !== searchSequence) {
            return;
        }

        if (!response.ok) {
            throw new Error('Search request failed.');
        }

        results.value = (await response.json()) as CustomerSearchResult[];
    } catch {
        if (currentSequence !== searchSequence) {
            return;
        }

        results.value = [];
        searchError.value = 'Customer search is unavailable right now.';
    } finally {
        if (currentSequence === searchSequence) {
            loading.value = false;
        }
    }
};

watch(query, () => {
    clearDebounce();
    searchSequence += 1;

    if (!canSearch.value) {
        resetSearchState();
        return;
    }

    debounceTimer.value = setTimeout(() => {
        searchCustomers(normalizedQuery.value);
    }, 300);
});

const selectCustomer = (customer: CustomerSearchResult) => {
    emit('update:modelValue', customer);
    query.value = '';
    resetSearchState();
};

const clearSelectedCustomer = () => {
    emit('update:modelValue', null);
};

onBeforeUnmount(() => {
    clearDebounce();
    searchSequence += 1;
});
</script>

<template>
    <div class="space-y-3">
        <Label for="customer_search">Find existing customer</Label>

        <div v-if="modelValue" class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                    <UserCheck class="size-4" />
                </div>

                <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-foreground">{{ modelValue.name }}</div>
                    <div class="truncate text-sm text-muted-foreground">{{ modelValue.phone }}</div>
                </div>
            </div>

            <Button type="button" variant="outline" size="sm" @click="clearSelectedCustomer">
                <X class="size-4" />
                Clear
            </Button>
        </div>

        <div v-else class="space-y-2">
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    id="customer_search"
                    v-model="query"
                    class="pl-9"
                    type="search"
                    autocomplete="off"
                    placeholder="Search by name or phone"
                />
            </div>

            <div class="min-h-10 overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div v-if="!canSearch" class="px-3 py-2 text-sm text-muted-foreground">Type at least 2 characters.</div>

                <div v-else-if="loading" class="px-3 py-2 text-sm text-muted-foreground">Searching customers...</div>

                <div v-else-if="searchError" class="px-3 py-2 text-sm text-red-600 dark:text-red-500">{{ searchError }}</div>

                <div v-else-if="hasSearched && results.length === 0" class="px-3 py-2 text-sm text-muted-foreground">No customers found.</div>

                <div v-else-if="results.length > 0" class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <button
                        v-for="customer in results"
                        :key="customer.id"
                        type="button"
                        class="flex w-full flex-col px-3 py-2 text-left hover:bg-muted/60 focus-visible:bg-muted/60 focus-visible:outline-none"
                        @click="selectCustomer(customer)"
                    >
                        <span class="text-sm font-medium text-foreground">{{ customer.name }}</span>
                        <span class="text-sm text-muted-foreground">{{ customer.phone }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
