<script setup lang="ts">
import PublicBrand from '@/components/public/PublicBrand.vue';
import { Sheet, SheetContent, SheetDescription, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Link } from '@inertiajs/vue3';
import { CircleHelp, ClipboardList, Menu, Plus, Store, Tag, UserRound, Workflow, X } from 'lucide-vue-next';
import { ref } from 'vue';

type InfoPanel = 'how-it-works' | 'prices' | 'help';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();

const activeInfoPanel = ref<InfoPanel | null>(null);
const mobileOpen = ref(false);

const openInfoPanel = (panel: InfoPanel) => {
    activeInfoPanel.value = panel;
    mobileOpen.value = false;
};

const closeMobileMenu = () => {
    mobileOpen.value = false;
};

const closeInfoPanel = () => {
    activeInfoPanel.value = null;
};
</script>

<template>
    <div class="public-page flex min-h-dvh">
        <aside data-testid="desktop-sidebar" class="hidden w-72 shrink-0 flex-col border-r border-[#dfe4e4] bg-white px-5 py-6 lg:flex">
            <Link :href="route('home')" aria-label="AutoService home">
                <PublicBrand />
            </Link>

            <nav aria-label="Public navigation" class="mt-8 flex flex-1 flex-col gap-1.5">
                <Link
                    :href="route('home')"
                    class="public-focus flex min-h-11 items-center gap-3 rounded-xl bg-[#e9f3f2] px-3.5 text-sm font-semibold text-[#0b1f33]"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    New request
                </Link>
                <slot v-if="$slots.history" name="history" />
                <Link
                    v-else
                    :href="route('customer-portal.index')"
                    class="public-focus flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm font-medium text-[#43566c] transition hover:bg-slate-100 hover:text-[#0b1f33]"
                >
                    <ClipboardList class="size-4" aria-hidden="true" />
                    My requests
                </Link>
                <div class="my-3 border-t border-slate-200" />
                <button
                    type="button"
                    aria-label="Open How it works"
                    class="public-focus flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-left text-sm font-medium text-[#43566c] transition hover:bg-slate-100 hover:text-[#0b1f33]"
                    @click="openInfoPanel('how-it-works')"
                >
                    <Workflow class="size-4" aria-hidden="true" />
                    How it works
                </button>
                <button
                    type="button"
                    aria-label="Open Prices"
                    class="public-focus flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-left text-sm font-medium text-[#43566c] transition hover:bg-slate-100 hover:text-[#0b1f33]"
                    @click="openInfoPanel('prices')"
                >
                    <Tag class="size-4" aria-hidden="true" />
                    Prices
                </button>
                <button
                    type="button"
                    aria-label="Open Help"
                    class="public-focus flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-left text-sm font-medium text-[#43566c] transition hover:bg-slate-100 hover:text-[#0b1f33]"
                    @click="openInfoPanel('help')"
                >
                    <CircleHelp class="size-4" aria-hidden="true" />
                    Help
                </button>

                <div class="mt-auto border-t border-slate-200 pt-4">
                    <Link
                        v-if="canRegister"
                        :href="adminRegisterUrl"
                        class="public-focus flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm font-medium text-[#43566c] transition hover:bg-slate-100 hover:text-[#0b1f33]"
                    >
                        <Store class="size-4" aria-hidden="true" />
                        For workshops
                    </Link>
                    <Link
                        v-if="canLogin"
                        :href="adminLoginUrl"
                        class="public-focus flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm font-medium text-[#607086] transition hover:bg-slate-100 hover:text-[#0b1f33]"
                    >
                        <UserRound class="size-4" aria-hidden="true" />
                        Staff login
                    </Link>
                </div>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-[#dfe4e4] bg-white px-4 lg:hidden">
                <Link :href="route('home')" aria-label="AutoService home" @click="closeMobileMenu">
                    <PublicBrand compact />
                </Link>

                <Sheet v-model:open="mobileOpen">
                    <SheetTrigger as-child>
                        <button
                            type="button"
                            aria-label="Open navigation"
                            class="public-focus inline-flex size-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-[#0b1f33]"
                        >
                            <Menu class="size-5" aria-hidden="true" />
                        </button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[min(21rem,88vw)] overflow-y-auto bg-white p-5">
                        <SheetTitle class="sr-only">Navigation</SheetTitle>
                        <SheetDescription class="sr-only">Public navigation and workshop account links.</SheetDescription>
                        <PublicBrand />
                        <nav aria-label="Mobile public navigation" class="mt-8 flex flex-col gap-2">
                            <Link
                                :href="route('home')"
                                class="flex min-h-11 items-center gap-3 rounded-xl bg-[#e9f3f2] px-3.5 text-sm font-semibold text-[#0b1f33]"
                                @click="closeMobileMenu"
                            >
                                <Plus class="size-4" aria-hidden="true" />
                                New request
                            </Link>
                            <slot v-if="$slots.history" name="history" />
                            <Link
                                v-else
                                :href="route('customer-portal.index')"
                                class="flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm font-medium text-[#43566c]"
                                @click="closeMobileMenu"
                            >
                                <ClipboardList class="size-4" aria-hidden="true" />
                                My requests
                            </Link>
                            <button
                                type="button"
                                class="flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-left text-sm text-[#43566c]"
                                @click="openInfoPanel('how-it-works')"
                            >
                                <Workflow class="size-4" aria-hidden="true" />
                                How it works
                            </button>
                            <button
                                type="button"
                                class="flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-left text-sm text-[#43566c]"
                                @click="openInfoPanel('prices')"
                            >
                                <Tag class="size-4" aria-hidden="true" />
                                Prices
                            </button>
                            <button
                                type="button"
                                class="flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-left text-sm text-[#43566c]"
                                @click="openInfoPanel('help')"
                            >
                                <CircleHelp class="size-4" aria-hidden="true" />
                                Help
                            </button>
                            <Link
                                v-if="canRegister"
                                :href="adminRegisterUrl"
                                class="flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm text-[#43566c]"
                                @click="closeMobileMenu"
                            >
                                <Store class="size-4" aria-hidden="true" />
                                For workshops
                            </Link>
                            <Link
                                v-if="canLogin"
                                :href="adminLoginUrl"
                                class="flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm text-[#607086]"
                                @click="closeMobileMenu"
                            >
                                <UserRound class="size-4" aria-hidden="true" />
                                Staff login
                            </Link>
                        </nav>
                    </SheetContent>
                </Sheet>
            </header>

            <main class="min-w-0 flex-1">
                <slot />
            </main>
        </div>

        <div
            v-if="activeInfoPanel"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1f33]/45 p-4"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="`info-panel-${activeInfoPanel}`"
            @click.self="closeInfoPanel"
        >
            <section class="relative w-full max-w-lg rounded-3xl border border-[#dfe4e4] bg-white p-6 shadow-2xl sm:p-8">
                <button
                    type="button"
                    aria-label="Close information panel"
                    class="public-focus absolute right-4 top-4 inline-flex size-10 items-center justify-center rounded-xl text-[#607086] hover:bg-slate-100"
                    @click="closeInfoPanel"
                >
                    <X class="size-5" aria-hidden="true" />
                </button>

                <template v-if="activeInfoPanel === 'how-it-works'">
                    <h2 id="info-panel-how-it-works" class="pr-12 text-2xl font-semibold tracking-tight text-[#0b1f33]">How it works</h2>
                    <ol class="mt-5 space-y-3 text-sm leading-6 text-[#43566c]">
                        <li>1. Describe what is happening with your car.</li>
                        <li>2. Add your contact and vehicle details.</li>
                        <li>3. Choose a workshop and review the request.</li>
                        <li>4. The workshop contacts you to confirm the next step.</li>
                    </ol>
                </template>
                <template v-else-if="activeInfoPanel === 'prices'">
                    <h2 id="info-panel-prices" class="pr-12 text-2xl font-semibold tracking-tight text-[#0b1f33]">Prices</h2>
                    <p class="mt-5 text-sm leading-6 text-[#43566c]">
                        Describe the issue first. The workshop reviews or diagnoses it, then confirms the estimate before work proceeds with the
                        applicable customer confirmation.
                    </p>
                </template>
                <template v-else>
                    <h2 id="info-panel-help" class="pr-12 text-2xl font-semibold tracking-tight text-[#0b1f33]">Help</h2>
                    <p class="mt-5 text-sm leading-6 text-[#43566c]">
                        Share the symptoms in your own words. You can review and edit every answer before sending the request to a workshop.
                    </p>
                </template>
            </section>
        </div>
    </div>
</template>
