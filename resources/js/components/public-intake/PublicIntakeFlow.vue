<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Building2, Car, Check, ChevronRight, Phone, Send, UserRound, X } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import PublicIntakeTranscript from './PublicIntakeTranscript.vue';
import {
    activeStateFor,
    advanceConversation,
    beginEdit,
    completedConversationFor,
    editorStateFor,
    finishOptionalDetails,
    responseControlFor,
    returnStateFor,
    stateForServerErrors,
    submitOnConfirmation,
    type ConversationState,
    type EditableAnswer,
    type EditContext,
    type IntakeErrors,
} from './state';
import type { PublicIntakePayload, WorkshopOption } from './types';

const props = defineProps<{ workshops: WorkshopOption[] }>();
const emit = defineEmits<{
    'expanded-change': [expanded: boolean];
}>();

const conversationState = ref<ConversationState>('problem');
const editContext = ref<EditContext | null>(null);
const cancelPending = ref(false);
const announcement = ref('What is happening with your car?');
const transcriptViewport = ref<HTMLElement | null>(null);

const form = useForm<PublicIntakePayload>({
    message: '',
    phone: '',
    customer_name: '',
    vehicle: { brand: '', model: '', year: null, license_plate: '' },
    workshop_id: null,
    website: '',
});

const activeState = computed(() => activeStateFor(conversationState.value, editContext.value));
const activeControl = computed(() => responseControlFor(activeState.value));
const chatExpanded = computed(() => conversationState.value !== 'problem' || editContext.value !== null);
const editingState = computed(() => editContext.value?.answer ?? null);
const editingFromConfirmation = computed(() => editContext.value?.returnState === 'confirmation');
const completedConversation = computed(() => completedConversationFor(conversationState.value));
const selectedWorkshop = computed(() => props.workshops.find((workshop) => workshop.id === form.workshop_id));
const vehicleSummary = computed(() =>
    [form.vehicle.brand, form.vehicle.model, form.vehicle.year, form.vehicle.license_plate].filter(Boolean).join(' · '),
);
const focusTargets: Partial<Record<ConversationState, string>> = {
    problem: 'message',
    phone: 'phone',
    'optional-details': 'add-name',
    name: 'customer-name',
    vehicle: 'vehicle-brand',
    workshop: props.workshops[0] ? `workshop-option-${props.workshops[0].id}` : 'workshop-question',
    confirmation: 'send-request',
};

watch(chatExpanded, (expanded) => {
    emit('expanded-change', expanded);
});

const scrollTranscriptToEnd = () => {
    const transcript = transcriptViewport.value;
    if (!transcript) return;

    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    transcript.scrollTo({
        top: transcript.scrollHeight,
        behavior: reduceMotion ? 'auto' : 'smooth',
    });
};

const focusActiveState = async (state: ConversationState, errors: IntakeErrors = {}) => {
    announcement.value = Object.keys(errors).length > 0 ? 'Please check the highlighted answer.' : `Now showing ${state.replace('-', ' ')}.`;
    await nextTick();
    document.getElementById(focusTargets[state] ?? '')?.focus();
    scrollTranscriptToEnd();
};

const moveToState = async (state: ConversationState, errors: IntakeErrors = {}) => {
    conversationState.value = state;
    await focusActiveState(state, errors);
};

const continueConversation = async () => {
    const answeredState = activeState.value;
    const result = advanceConversation(answeredState, form.data(), props.workshops.length > 0);

    if (result.error) {
        form.setError(result.error.field, result.error.message);
        await focusActiveState(answeredState, { [result.error.field]: result.error.message });
        return;
    }

    if (answeredState === 'problem') form.clearErrors('message');
    if (answeredState === 'phone') form.clearErrors('phone');
    if (editContext.value) {
        const returnState = returnStateFor(editContext.value);
        editContext.value = null;
        await moveToState(returnState);
        return;
    }

    await moveToState(result.state);
};

const handleProblemEnter = (event: KeyboardEvent) => {
    if (chatExpanded.value || event.shiftKey) return;

    event.preventDefault();
    void continueConversation();
};

const problemPrompts = ['The car will not start', 'I hear an unusual noise', 'I need scheduled maintenance'] as const;

const selectProblemPrompt = async (prompt: string) => {
    form.message = prompt;
    form.clearErrors('message');
    await nextTick();
    document.getElementById('message')?.focus();
};

const selectOptionalDetails = async (answer: 'name' | 'vehicle') => {
    editContext.value = beginEdit(answer, conversationState.value);
    await focusActiveState(answer);
};

const saveOptionalDetails = async () => {
    if (editContext.value?.answer === 'name' && form.errors.customer_name) {
        await focusActiveState('name', { customer_name: form.errors.customer_name });
        return;
    }

    if (editContext.value?.answer === 'vehicle') {
        if (form.vehicle.year !== null && (form.vehicle.year < 1886 || form.vehicle.year > 2100)) {
            form.setError('vehicle.year', 'Please enter a year between 1886 and 2100.');
        }

        const vehicleFields = [
            ['vehicle.brand', 'vehicle-brand'],
            ['vehicle.model', 'vehicle-model'],
            ['vehicle.year', 'vehicle-year'],
            ['vehicle.license_plate', 'vehicle-license-plate'],
        ] as const;
        const invalidField = vehicleFields.find(([field]) => form.errors[field]);

        if (invalidField) {
            await focusActiveState('vehicle', form.errors);
            document.getElementById(invalidField[1])?.focus();
            return;
        }
    }

    const nextState = editContext.value ? returnStateFor(editContext.value) : conversationState.value;
    editContext.value = null;
    await moveToState(nextState);
};

const skipOptionalDetails = async () => {
    if (editContext.value?.answer === 'name') {
        form.customer_name = '';
    } else if (editContext.value?.answer === 'vehicle') {
        form.vehicle = { brand: '', model: '', year: null, license_plate: '' };
    }

    await saveOptionalDetails();
};

const continueFromOptionalDetails = async () => {
    await moveToState(finishOptionalDetails());
};

const selectWorkshop = async () => {
    form.clearErrors('workshop_id');
    const result = advanceConversation('workshop', form.data(), props.workshops.length > 0);
    const nextState = editContext.value ? returnStateFor(editContext.value) : result.state;
    editContext.value = null;
    await moveToState(nextState);
};

const finishWorkshopEdit = async () => {
    await selectWorkshop();
};

const editAnswer = async (answer: EditableAnswer) => {
    cancelPending.value = false;
    editContext.value = beginEdit(editorStateFor(answer), conversationState.value);
    await focusActiveState(editContext.value.answer);
};

const requestCancellation = async () => {
    cancelPending.value = true;
    announcement.value = 'Confirm whether to cancel this unsent draft.';
    await nextTick();
    document.getElementById('keep-editing')?.focus();
};

const dismissCancellation = async () => {
    cancelPending.value = false;
    await nextTick();
    document.getElementById('cancel-request')?.focus();
};

const resetDraft = async () => {
    form.reset();
    form.clearErrors();
    editContext.value = null;
    cancelPending.value = false;
    await moveToState('problem');
};

const submit = () => {
    submitOnConfirmation(conversationState.value, form.processing, () => {
        form.post(route('public-intake.store'), {
            preserveScroll: true,
            onError: async (errors) => {
                const targetState = stateForServerErrors(errors);
                if (!targetState) return;

                editContext.value = beginEdit(targetState as EditableAnswer, conversationState.value);
                await focusActiveState(targetState, errors);
            },
        });
    });
};
</script>

<template>
    <section aria-labelledby="public-intake-title" class="w-full">
        <h1 id="public-intake-title" class="sr-only">Request car service</h1>
        <p class="sr-only" aria-live="polite" aria-atomic="true">{{ announcement }}</p>

        <form
            :data-testid="chatExpanded ? 'intake-chat' : 'intake-starter'"
            :class="
                chatExpanded
                    ? 'public-card flex h-[calc(100dvh-7rem)] max-h-[48rem] min-h-[32rem] flex-col overflow-hidden'
                    : 'mx-auto w-full max-w-3xl'
            "
            @submit.prevent="submit"
        >
            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden" />

            <section v-if="!chatExpanded" data-testid="intake-intro" aria-labelledby="intake-starter-title" class="mb-7 text-center sm:mb-9">
                <p class="public-kicker">Start a service request</p>
                <h2 id="intake-starter-title" class="mt-3 text-3xl font-semibold tracking-[-0.035em] text-[#0b1f33] sm:text-4xl">
                    What is happening with your car?
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-[#607086] sm:text-base">
                    Describe the issue in your own words. The selected workshop will confirm the details with you.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <button
                        v-for="prompt in problemPrompts"
                        :key="prompt"
                        type="button"
                        class="public-focus rounded-full border border-[#dfe4e4] bg-white px-4 py-2.5 text-sm font-medium text-[#43566c] shadow-sm transition hover:border-[#0e7c86]/50 hover:text-[#0e7c86]"
                        @click="selectProblemPrompt(prompt)"
                    >
                        {{ prompt }}
                    </button>
                </div>
            </section>

            <div
                v-if="chatExpanded"
                ref="transcriptViewport"
                data-testid="intake-transcript"
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-5 sm:px-7 sm:py-7 lg:px-9"
            >
                <div class="flex min-h-full flex-col justify-end">
                    <PublicIntakeTranscript
                        :completed="completedConversation"
                        :active-state="activeState"
                        :message="form.message"
                        :phone="form.phone"
                        :customer-name="form.customer_name"
                        :vehicle-summary="vehicleSummary"
                        :selected-workshop-name="selectedWorkshop?.name"
                        :editing-answer="editContext?.answer"
                        @edit="editAnswer"
                    />
                </div>
            </div>

            <div
                id="active-response"
                :data-testid="chatExpanded ? 'intake-composer' : 'intake-starter-composer'"
                :class="chatExpanded ? 'shrink-0 border-t border-slate-200 bg-white px-4 py-4 sm:px-7 lg:px-9' : 'w-full'"
            >
                <div
                    v-if="activeControl === 'problem-composer'"
                    data-testid="problem-composer"
                    :class="
                        chatExpanded
                            ? 'rounded-[1.75rem] border border-slate-200 bg-white p-2 shadow-[0_18px_48px_-24px_rgba(15,23,42,0.5)]'
                            : 'flex flex-wrap items-end gap-2 rounded-[1.75rem] border border-slate-200 bg-white p-2 shadow-[0_18px_48px_-24px_rgba(15,23,42,0.5)]'
                    "
                >
                    <label for="message" class="sr-only">Describe your car problem</label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        name="message"
                        :rows="chatExpanded ? 3 : 1"
                        maxlength="5000"
                        autofocus
                        :class="
                            chatExpanded
                                ? 'w-full resize-none rounded-2xl border-0 bg-slate-50 px-4 py-3 text-base leading-6 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0e7c86]/25'
                                : 'min-h-12 min-w-0 flex-1 resize-none rounded-2xl border-0 bg-slate-50 px-4 py-3 text-base leading-6 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0e7c86]/25'
                        "
                        placeholder="Describe the issue..."
                        :aria-invalid="Boolean(form.errors.message)"
                        aria-describedby="message-error"
                        @input="form.clearErrors('message')"
                        @keydown.enter="handleProblemEnter"
                    />
                    <div :class="chatExpanded ? 'flex items-center justify-between gap-3 px-2 pb-1 pt-2' : 'contents'">
                        <p
                            v-if="form.errors.message"
                            id="message-error"
                            :class="
                                chatExpanded
                                    ? 'text-sm font-medium text-red-700'
                                    : 'order-first basis-full px-3 pt-1 text-sm font-medium text-red-700'
                            "
                        >
                            {{ form.errors.message }}
                        </p>
                        <span v-else-if="chatExpanded" class="text-xs text-slate-500">The workshop will confirm the diagnosis.</span>
                        <button
                            type="button"
                            :class="chatExpanded ? 'public-button-primary ml-auto min-h-11' : 'public-button-primary min-h-12 shrink-0'"
                            @click="continueConversation"
                        >
                            {{ editingState ? 'Save changes' : 'Send' }} <Send class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <div
                    v-else-if="activeControl === 'phone-input'"
                    class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_12px_36px_-22px_rgba(15,23,42,0.5)] sm:flex-row sm:items-center"
                >
                    <label for="phone" class="sr-only">Phone number</label>
                    <div class="flex min-w-0 flex-1 items-center gap-3 rounded-xl bg-slate-50 px-4">
                        <Phone class="size-4 shrink-0 text-slate-500" aria-hidden="true" />
                        <input
                            id="phone"
                            v-model="form.phone"
                            name="phone"
                            type="tel"
                            maxlength="50"
                            autocomplete="tel"
                            class="min-h-12 w-full border-0 bg-transparent p-0 text-base focus:ring-0"
                            placeholder="Enter your phone number..."
                            :aria-invalid="Boolean(form.errors.phone)"
                            aria-describedby="phone-error"
                            @input="form.clearErrors('phone')"
                            @keydown.enter.prevent="continueConversation"
                        />
                    </div>
                    <button type="button" class="public-button-primary min-h-12" @click="continueConversation">
                        {{ editingState ? 'Save changes' : 'Continue' }} <ChevronRight class="size-4" aria-hidden="true" />
                    </button>
                    <p v-if="form.errors.phone" id="phone-error" class="px-2 text-sm font-medium text-red-700 sm:basis-full">
                        {{ form.errors.phone }}
                    </p>
                </div>

                <div v-else-if="activeControl === 'optional-actions'" class="grid gap-2 sm:grid-cols-3">
                    <button
                        id="add-name"
                        type="button"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm hover:border-[#0e7c86]/50 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                        @click="selectOptionalDetails('name')"
                    >
                        <UserRound class="size-4 text-[#0e7c86]" aria-hidden="true" /> {{ form.customer_name ? 'Edit name' : 'Add name' }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm hover:border-[#0e7c86]/50 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                        @click="selectOptionalDetails('vehicle')"
                    >
                        <Car class="size-4 text-[#0e7c86]" aria-hidden="true" /> {{ vehicleSummary ? 'Edit car details' : 'Add car details' }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-[#0e7c86]/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                        @click="continueFromOptionalDetails"
                    >
                        Continue <ChevronRight class="size-4" aria-hidden="true" />
                    </button>
                </div>

                <div
                    v-else-if="activeControl === 'name-input'"
                    class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:flex-row sm:flex-wrap"
                >
                    <label for="customer-name" class="sr-only">Your name</label>
                    <input
                        id="customer-name"
                        v-model="form.customer_name"
                        name="customer_name"
                        autocomplete="name"
                        maxlength="255"
                        class="min-h-12 min-w-0 flex-1 rounded-xl border-0 bg-slate-50 px-4 text-base focus:ring-2 focus:ring-[#0e7c86]/25"
                        placeholder="Your name (optional)"
                        :aria-invalid="Boolean(form.errors.customer_name)"
                        :aria-describedby="form.errors.customer_name ? 'customer-name-error' : undefined"
                        @input="form.clearErrors('customer_name')"
                    />
                    <p v-if="form.errors.customer_name" id="customer-name-error" class="px-2 text-sm font-medium text-red-700 sm:basis-full">
                        {{ form.errors.customer_name }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="min-h-11 flex-1 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:border-[#0e7c86]/50 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                            @click="skipOptionalDetails"
                        >
                            Skip
                        </button>
                        <button type="button" class="public-button-primary flex-1" @click="saveOptionalDetails">
                            {{ editingFromConfirmation ? 'Save changes' : 'Save name' }}
                        </button>
                    </div>
                </div>

                <div v-else-if="activeControl === 'vehicle-inputs'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="vehicle-brand" class="text-sm font-medium text-slate-700">Brand</label
                            ><input
                                id="vehicle-brand"
                                v-model="form.vehicle.brand"
                                name="vehicle[brand]"
                                maxlength="255"
                                class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:border-[#0e7c86] focus:ring-[#0e7c86]/20"
                                placeholder="Opel"
                                :aria-invalid="Boolean(form.errors['vehicle.brand'])"
                                :aria-describedby="form.errors['vehicle.brand'] ? 'vehicle-brand-error' : undefined"
                                @input="form.clearErrors('vehicle.brand')"
                            />
                            <p v-if="form.errors['vehicle.brand']" id="vehicle-brand-error" class="mt-1 text-sm font-medium text-red-700">
                                {{ form.errors['vehicle.brand'] }}
                            </p>
                        </div>
                        <div>
                            <label for="vehicle-model" class="text-sm font-medium text-slate-700">Model</label
                            ><input
                                id="vehicle-model"
                                v-model="form.vehicle.model"
                                name="vehicle[model]"
                                maxlength="255"
                                class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:border-[#0e7c86] focus:ring-[#0e7c86]/20"
                                placeholder="Insignia"
                                :aria-invalid="Boolean(form.errors['vehicle.model'])"
                                :aria-describedby="form.errors['vehicle.model'] ? 'vehicle-model-error' : undefined"
                                @input="form.clearErrors('vehicle.model')"
                            />
                            <p v-if="form.errors['vehicle.model']" id="vehicle-model-error" class="mt-1 text-sm font-medium text-red-700">
                                {{ form.errors['vehicle.model'] }}
                            </p>
                        </div>
                        <div>
                            <label for="vehicle-year" class="text-sm font-medium text-slate-700">Year</label
                            ><input
                                id="vehicle-year"
                                v-model.number="form.vehicle.year"
                                name="vehicle[year]"
                                type="number"
                                inputmode="numeric"
                                min="1886"
                                max="2100"
                                class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:border-[#0e7c86] focus:ring-[#0e7c86]/20"
                                placeholder="2017"
                                :aria-invalid="Boolean(form.errors['vehicle.year'])"
                                :aria-describedby="form.errors['vehicle.year'] ? 'vehicle-year-error' : undefined"
                                @input="form.clearErrors('vehicle.year')"
                            />
                            <p v-if="form.errors['vehicle.year']" id="vehicle-year-error" class="mt-1 text-sm font-medium text-red-700">
                                {{ form.errors['vehicle.year'] }}
                            </p>
                        </div>
                        <div>
                            <label for="vehicle-license-plate" class="text-sm font-medium text-slate-700">License plate</label
                            ><input
                                id="vehicle-license-plate"
                                v-model="form.vehicle.license_plate"
                                name="vehicle[license_plate]"
                                maxlength="255"
                                class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 uppercase focus:border-[#0e7c86] focus:ring-[#0e7c86]/20"
                                placeholder="AA 1234 BB"
                                :aria-invalid="Boolean(form.errors['vehicle.license_plate'])"
                                :aria-describedby="form.errors['vehicle.license_plate'] ? 'vehicle-license-plate-error' : undefined"
                                @input="form.clearErrors('vehicle.license_plate')"
                            />
                            <p
                                v-if="form.errors['vehicle.license_plate']"
                                id="vehicle-license-plate-error"
                                class="mt-1 text-sm font-medium text-red-700"
                            >
                                {{ form.errors['vehicle.license_plate'] }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <button
                            type="button"
                            class="min-h-11 rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-600 hover:border-[#0e7c86]/50 hover:text-[#0e7c86] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0e7c86] focus-visible:ring-offset-2"
                            @click="skipOptionalDetails"
                        >
                            Skip
                        </button>
                        <button type="button" class="public-button-primary" @click="saveOptionalDetails">
                            {{ editingFromConfirmation ? 'Save changes' : 'Save car details' }}
                        </button>
                    </div>
                </div>

                <div v-else-if="activeControl === 'workshop-cards'">
                    <fieldset v-if="workshops.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <legend class="sr-only">Available workshops</legend>
                        <label
                            v-for="workshop in workshops"
                            :key="workshop.id"
                            class="relative flex min-h-36 cursor-pointer flex-col rounded-[1.25rem] border p-5 shadow-[0_12px_30px_-24px_rgba(11,31,51,0.4)] transition focus-within:ring-2 focus-within:ring-[#0e7c86] focus-within:ring-offset-2"
                            :class="
                                form.workshop_id === workshop.id
                                    ? 'border-[#0e7c86] bg-[#e9f3f2] ring-1 ring-[#0e7c86]'
                                    : 'border-[#dfe4e4] bg-white hover:border-[#0e7c86]/60 hover:shadow-md'
                            "
                        >
                            <input
                                :id="`workshop-option-${workshop.id}`"
                                v-model="form.workshop_id"
                                type="radio"
                                name="workshop_id"
                                :value="workshop.id"
                                class="peer sr-only focus-visible:ring-2"
                                :aria-invalid="Boolean(form.errors.workshop_id)"
                                @change="selectWorkshop"
                            />
                            <span class="flex items-start justify-between gap-3"
                                ><span class="flex size-11 items-center justify-center rounded-2xl bg-[#0b1f33] text-white"
                                    ><Building2 class="size-5" aria-hidden="true" /></span
                                ><span
                                    class="flex size-5 items-center justify-center rounded-full border"
                                    :class="
                                        form.workshop_id === workshop.id
                                            ? 'border-[#0e7c86] bg-[#0e7c86] text-white'
                                            : 'border-slate-400 text-transparent'
                                    "
                                    ><Check class="size-3.5" aria-hidden="true" /></span
                            ></span>
                            <span class="mt-4 font-semibold text-slate-950">{{ workshop.name }}</span>
                            <span class="mt-1 text-sm text-slate-500">Send request here</span>
                        </label>
                    </fieldset>
                    <div v-else class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                        No workshops are available to receive requests right now.
                    </div>
                    <p v-if="form.errors.workshop_id" class="mt-3 text-sm font-medium text-red-700">{{ form.errors.workshop_id }}</p>
                    <div v-if="editingState === 'workshop'" class="mt-4 flex justify-end">
                        <button type="button" class="public-button-primary w-full sm:w-auto" @click="finishWorkshopEdit">Save changes</button>
                    </div>
                </div>

                <div v-else-if="activeControl === 'send-action'">
                    <div
                        v-if="cancelPending"
                        role="alertdialog"
                        aria-labelledby="cancel-draft-title"
                        aria-describedby="cancel-draft-description"
                        class="rounded-2xl border border-red-200 bg-red-50 p-4 sm:p-5"
                    >
                        <h2 id="cancel-draft-title" class="font-semibold text-slate-950">Cancel this draft?</h2>
                        <p id="cancel-draft-description" class="mt-1 text-sm leading-6 text-slate-600">
                            Your unsent answers will be cleared. No request has been created yet.
                        </p>
                        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <button
                                id="keep-editing"
                                type="button"
                                class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 hover:border-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2"
                                @click="dismissCancellation"
                            >
                                Keep editing
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-red-700 px-5 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700 focus-visible:ring-offset-2"
                                @click="resetDraft"
                            >
                                <X class="size-4" aria-hidden="true" /> Yes, cancel draft
                            </button>
                        </div>
                    </div>
                    <div v-else class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            id="cancel-request"
                            type="button"
                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-700 hover:border-red-300 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 sm:w-auto"
                            @click="requestCancellation"
                        >
                            <X class="size-4" aria-hidden="true" /> Cancel request
                        </button>
                        <button
                            id="send-request"
                            type="submit"
                            :disabled="form.processing"
                            class="public-button-primary min-h-12 w-full px-6 sm:w-auto"
                        >
                            <Send class="size-4" aria-hidden="true" /> {{ form.processing ? 'Sending...' : 'Send request' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>
