<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowUp, Check, Phone, Wrench } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

const props = defineProps<{
    workshop: {
        name: string;
        slug: string;
    };
    intakeSubmitted?: boolean;
}>();

const exampleMessages = [
    'Opel Insignia, check engine light came on',
    'My brakes make noise when stopping',
    'Car shakes above 90 km/h',
];

const form = useForm({
    message: '',
});

const messageInput = ref<HTMLTextAreaElement | null>(null);
const canSubmit = computed(() => form.message.trim().length > 0 && !form.processing);

const fillExample = async (message: string) => {
    form.message = message;
    form.clearErrors('message');

    await nextTick();

    messageInput.value?.focus();
};

const submit = () => {
    if (!canSubmit.value) {
        return;
    }

    form.post(route('public-intake.store', props.workshop.slug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
        },
    });
};
</script>

<template>
    <Head :title="`${workshop.name} - Service Request`" />

    <main class="min-h-screen bg-[#f7f3ea] text-slate-900">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between px-5 py-5 sm:px-6">
            <Link :href="route('home')" class="flex items-center gap-3" aria-label="AutoService home">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#2f6471] text-white shadow-sm">
                    <Wrench class="size-5" aria-hidden="true" />
                </span>
                <span class="text-base font-semibold tracking-tight">AutoService</span>
            </Link>
        </header>

        <section class="mx-auto flex w-full max-w-5xl flex-col items-center px-5 pb-16 pt-10 sm:px-6 md:pb-24 md:pt-16">
            <div class="w-full max-w-3xl text-center">
                <p class="text-sm font-medium text-[#2f6471]">{{ workshop.name }}</p>
                <h1 class="mt-4 text-4xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-5xl">
                    How can we help with your car?
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                    Describe the problem in your own words. A service advisor will review your request and contact
                    you to confirm details and visit time.
                </p>
            </div>

            <div class="mt-10 w-full max-w-3xl">
                <section
                    v-if="intakeSubmitted"
                    class="rounded-lg border border-emerald-200 bg-white p-7 text-center shadow-[0_18px_60px_-32px_rgba(47,100,113,0.45)] sm:p-8"
                    aria-live="polite"
                >
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                        <Check class="size-6" aria-hidden="true" />
                    </span>
                    <h2 class="mt-5 text-xl font-semibold tracking-tight text-slate-950">Request received</h2>
                    <p class="mx-auto mt-3 max-w-xl leading-7 text-slate-600">
                        Request received. A service advisor will contact you to confirm details and visit time.
                    </p>
                    <div class="mt-6 flex items-center justify-center gap-2 text-sm text-slate-500">
                        <Phone class="size-4" aria-hidden="true" />
                        <span>Staff confirmation comes before diagnosis, pricing, or scheduling.</span>
                    </div>
                </section>

                <form v-else class="w-full" @submit.prevent="submit">
                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3 shadow-[0_18px_60px_-32px_rgba(47,100,113,0.55)] transition focus-within:border-[#2f6471]/50 focus-within:shadow-[0_24px_70px_-34px_rgba(47,100,113,0.65)]"
                    >
                        <label for="message" class="sr-only">Describe your car problem</label>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <textarea
                                id="message"
                                ref="messageInput"
                                v-model="form.message"
                                name="message"
                                rows="5"
                                class="min-h-36 w-full resize-none rounded-md border-0 bg-transparent px-3 py-3 text-base leading-7 text-slate-900 shadow-none outline-none placeholder:text-slate-400 focus:ring-0"
                                placeholder="Opel Insignia, check engine light came on, maybe sensors, when can I come?"
                                :aria-invalid="Boolean(form.errors.message)"
                                aria-describedby="message-error"
                            />

                            <button
                                type="submit"
                                :disabled="!canSubmit"
                                class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md bg-[#2f6471] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#285864] disabled:cursor-not-allowed disabled:opacity-45"
                            >
                                <ArrowUp class="size-4" aria-hidden="true" />
                                <span>{{ form.processing ? 'Sending...' : 'Send request' }}</span>
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="form.errors.message"
                        id="message-error"
                        class="mt-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {{ form.errors.message }}
                    </p>

                    <div class="mt-5 flex flex-wrap justify-center gap-2.5">
                        <button
                            v-for="example in exampleMessages"
                            :key="example"
                            type="button"
                            class="rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-600 shadow-sm transition hover:border-[#2f6471]/40 hover:bg-white hover:text-slate-950"
                            @click="fillExample(example)"
                        >
                            {{ example }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</template>
