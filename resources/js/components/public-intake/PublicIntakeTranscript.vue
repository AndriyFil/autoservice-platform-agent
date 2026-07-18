<script setup lang="ts">
import { Building2, Car, Check, MessageSquareText, Pencil, Phone, UserRound } from 'lucide-vue-next';
import type { CompletedConversation, ConversationState, EditableAnswer } from './state';

defineProps<{
    completed: CompletedConversation;
    activeState: ConversationState;
    message: string;
    phone: string;
    customerName: string;
    vehicleSummary: string;
    selectedWorkshopName?: string;
    editingAnswer?: EditableAnswer;
}>();

defineEmits<{
    edit: [answer: EditableAnswer];
}>();
</script>

<template>
    <div class="space-y-5 sm:space-y-6">
        <div class="flex items-start gap-3">
            <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0e7c86] text-white shadow-sm">
                <Car class="size-4.5" aria-hidden="true" />
            </span>
            <div class="max-w-[34rem] rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
                <p class="font-semibold leading-6 text-slate-950">What is happening with your car?</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Describe it in your own words.</p>
            </div>
        </div>

        <div v-if="completed.problem" class="flex items-end justify-end gap-2">
            <button
                v-if="editingAnswer !== 'problem'"
                type="button"
                class="mb-1 flex size-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                aria-label="Edit problem description"
                @click="$emit('edit', 'problem')"
            >
                <Pencil class="size-4" aria-hidden="true" />
            </button>
            <div
                class="max-w-[82%] whitespace-pre-line rounded-2xl rounded-br-md bg-[#e9f3f2] px-4 py-3 text-[15px] leading-6 text-slate-900 sm:max-w-[70%]"
            >
                {{ message }}
            </div>
        </div>

        <div v-if="completed.problem" class="flex items-start gap-3">
            <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0e7c86] text-white shadow-sm">
                <Phone class="size-4" aria-hidden="true" />
            </span>
            <div class="max-w-[34rem] rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
                <p class="font-semibold leading-6 text-slate-950">What phone number should the workshop call you on?</p>
            </div>
        </div>

        <div v-if="completed.phone" class="flex items-end justify-end gap-2">
            <button
                v-if="editingAnswer !== 'phone'"
                type="button"
                class="mb-1 flex size-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                aria-label="Edit phone number"
                @click="$emit('edit', 'phone')"
            >
                <Pencil class="size-4" aria-hidden="true" />
            </button>
            <div class="max-w-[82%] rounded-2xl rounded-br-md bg-[#e9f3f2] px-4 py-3 text-[15px] leading-6 text-slate-900 sm:max-w-[70%]">
                {{ phone }}
            </div>
        </div>

        <div v-if="completed.phone" class="flex items-start gap-3">
            <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0e7c86] text-white shadow-sm">
                <MessageSquareText class="size-4" aria-hidden="true" />
            </span>
            <div class="max-w-[34rem] rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
                <p class="font-semibold leading-6 text-slate-950">Would you like to add anything else?</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Your name and car details are optional.</p>
            </div>
        </div>

        <div v-if="activeState === 'name'" class="flex items-start gap-3">
            <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0e7c86] text-white shadow-sm">
                <UserRound class="size-4" aria-hidden="true" />
            </span>
            <div class="max-w-[34rem] rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
                <p class="font-semibold leading-6 text-slate-950">What name should we include with the request?</p>
            </div>
        </div>

        <div v-if="activeState === 'vehicle'" class="flex items-start gap-3">
            <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0e7c86] text-white shadow-sm">
                <Car class="size-4" aria-hidden="true" />
            </span>
            <div class="max-w-[34rem] rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
                <p class="font-semibold leading-6 text-slate-950">Add the car details you know.</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Every field is optional.</p>
            </div>
        </div>

        <div v-if="customerName && activeState !== 'name'" class="flex items-end justify-end gap-2">
            <button
                v-if="editingAnswer !== 'name'"
                type="button"
                class="mb-1 flex size-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                aria-label="Edit name"
                @click="$emit('edit', 'name')"
            >
                <Pencil class="size-4" aria-hidden="true" />
            </button>
            <div class="max-w-[82%] rounded-2xl rounded-br-md bg-[#e9f3f2] px-4 py-3 text-[15px] leading-6 text-slate-900 sm:max-w-[70%]">
                Name: {{ customerName }}
            </div>
        </div>

        <div v-if="vehicleSummary && activeState !== 'vehicle'" class="flex items-end justify-end gap-2">
            <button
                v-if="editingAnswer !== 'vehicle'"
                type="button"
                class="mb-1 flex size-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                aria-label="Edit car details"
                @click="$emit('edit', 'vehicle')"
            >
                <Pencil class="size-4" aria-hidden="true" />
            </button>
            <div class="max-w-[82%] rounded-2xl rounded-br-md bg-[#e9f3f2] px-4 py-3 text-[15px] leading-6 text-slate-900 sm:max-w-[70%]">
                Car: {{ vehicleSummary }}
            </div>
        </div>

        <div v-if="completed.optionalDetails || activeState === 'workshop'" class="flex items-start gap-3">
            <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0e7c86] text-white shadow-sm">
                <Building2 class="size-4" aria-hidden="true" />
            </span>
            <div
                id="workshop-question"
                tabindex="-1"
                class="max-w-[34rem] rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-sm outline-none sm:px-5"
            >
                <p class="font-semibold leading-6 text-slate-950">Where should we send your request?</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Choose one workshop below.</p>
            </div>
        </div>

        <div v-if="completed.workshop && selectedWorkshopName" class="flex items-end justify-end gap-2">
            <button
                v-if="editingAnswer !== 'workshop'"
                type="button"
                class="mb-1 flex size-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                aria-label="Edit workshop"
                @click="$emit('edit', 'workshop')"
            >
                <Pencil class="size-4" aria-hidden="true" />
            </button>
            <div class="max-w-[82%] rounded-2xl rounded-br-md bg-[#e9f3f2] px-4 py-3 text-[15px] font-medium leading-6 text-slate-900 sm:max-w-[70%]">
                {{ selectedWorkshopName }}
            </div>
        </div>

        <div v-if="completed.confirmation && !editingAnswer && selectedWorkshopName" class="flex items-start gap-3">
            <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0e7c86] text-white shadow-sm">
                <Check class="size-4" aria-hidden="true" />
            </span>
            <div class="max-w-[34rem] rounded-2xl rounded-tl-md border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
                <p class="font-semibold leading-6 text-slate-950">Ready to send your request to {{ selectedWorkshopName }}?</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">The workshop will call you to confirm the details.</p>
            </div>
        </div>
    </div>
</template>
