<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { RepairOrderFormCustomer } from './types';

const props = defineProps<{
    id: string;
    modelValue: string;
    customers: RepairOrderFormCustomer[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const search = ref('');
const isOpen = ref(false);

const selectedCustomer = computed(() => props.customers.find((customer) => customer.id === Number(props.modelValue)) ?? null);

const customerLabel = (customer: RepairOrderFormCustomer): string => `${customer.name ?? 'Unnamed customer'} - ${customer.phone}`;

const normalizePhoneSearch = (phone: string): string => {
    const trimmed = phone.trim();
    const hasLeadingPlus = trimmed.startsWith('+');
    const digits = trimmed.replace(/[\s\-()]+/g, '').replace(/\D+/g, '');

    if (/^0\d{9}$/.test(digits)) {
        return `+38${digits}`;
    }

    if (/^380\d{9}$/.test(digits)) {
        return `+${digits}`;
    }

    return hasLeadingPlus ? `+${digits}` : digits;
};

const filteredCustomers = computed(() => {
    const query = search.value.trim().toLowerCase();
    const normalizedQuery = normalizePhoneSearch(search.value);

    if (props.customers.length === 0) {
        return [];
    }

    if (!query) {
        return props.customers.slice(0, 8);
    }

    return props.customers
        .filter((customer) => {
            const displayText = `${customer.name ?? ''} ${customer.phone}`.toLowerCase();

            return displayText.includes(query) || (normalizedQuery !== '' && customer.phoneNormalized.includes(normalizedQuery));
        })
        .slice(0, 8);
});

watch(
    selectedCustomer,
    (customer) => {
        if (customer) {
            search.value = customerLabel(customer);
        }
    },
    { immediate: true },
);

const openPicker = () => {
    isOpen.value = true;
};

const closePicker = () => {
    window.setTimeout(() => {
        isOpen.value = false;
    }, 100);
};

const updateSearch = (event: Event) => {
    search.value = (event.target as HTMLInputElement).value;
    isOpen.value = true;

    if (props.modelValue && selectedCustomer.value && search.value !== customerLabel(selectedCustomer.value)) {
        emit('update:modelValue', '');
    }
};

const selectCustomer = (customer: RepairOrderFormCustomer) => {
    emit('update:modelValue', String(customer.id));
    search.value = customerLabel(customer);
    isOpen.value = false;
};
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            :value="search"
            type="text"
            autocomplete="off"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            placeholder="Type customer name or phone"
            role="combobox"
            :aria-expanded="isOpen"
            aria-autocomplete="list"
            @focus="openPicker"
            @blur="closePicker"
            @input="updateSearch"
        />

        <div
            v-if="isOpen"
            class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-sidebar-border/70 bg-background shadow-md dark:border-sidebar-border"
            role="listbox"
        >
            <div v-if="customers.length === 0" class="px-3 py-2 text-sm text-muted-foreground">No customers available</div>

            <template v-else-if="filteredCustomers.length > 0">
                <button
                    v-for="customer in filteredCustomers"
                    :key="customer.id"
                    type="button"
                    class="flex w-full flex-col gap-0.5 px-3 py-2 text-left text-sm hover:bg-muted focus:bg-muted focus:outline-none"
                    role="option"
                    :aria-selected="String(customer.id) === modelValue"
                    @mousedown.prevent="selectCustomer(customer)"
                >
                    <span class="font-medium text-foreground">{{ customer.name ?? 'Unnamed customer' }}</span>
                    <span class="text-xs text-muted-foreground">{{ customer.phone }}</span>
                </button>
            </template>

            <div v-else class="px-3 py-2 text-sm text-muted-foreground">No customers found</div>
        </div>
    </div>
</template>
