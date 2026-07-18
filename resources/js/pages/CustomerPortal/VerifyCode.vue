<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CustomerPortalLayout from '@/layouts/CustomerPortalLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, KeyRound, LoaderCircle } from 'lucide-vue-next';

defineProps<{
    maskedPhone: string;
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('customer-portal.verify.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Check your phone" />

    <CustomerPortalLayout :can-login="canLogin" :can-register="canRegister" :admin-login-url="adminLoginUrl" :admin-register-url="adminRegisterUrl">
        <div class="flex size-12 items-center justify-center rounded-2xl bg-[#e9f3f2] text-[#0e7c86]">
            <KeyRound class="size-5" aria-hidden="true" />
        </div>

        <div class="mt-6">
            <p class="public-kicker">Phone verification</p>
            <h1 class="mt-2 text-[1.75rem] font-bold leading-tight tracking-[-0.035em] text-[#0b1f33]">Check your phone</h1>
            <p id="code-help" class="mt-3 text-[0.95rem] leading-6 text-[#607086]">
                Enter the six-digit code sent to <span class="font-medium text-slate-800">{{ maskedPhone }}</span
                >.
            </p>
        </div>

        <form class="mt-7 space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="code">Verification code</Label>
                <Input
                    id="code"
                    v-model="form.code"
                    class="public-field h-14 text-center font-mono text-lg tracking-[0.32em]"
                    type="text"
                    name="code"
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    placeholder="000000"
                    required
                    aria-describedby="code-help code-error"
                    :aria-invalid="form.errors.code ? true : undefined"
                />
                <div id="code-error" aria-live="polite">
                    <InputError :message="form.errors.code" />
                </div>
            </div>

            <Button type="submit" class="public-button-primary min-h-12 w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                <Check v-else class="size-4" aria-hidden="true" />
                Verify phone
            </Button>
        </form>

        <div class="mt-5 text-center">
            <Link
                :href="route('customer-portal.access.create')"
                class="public-focus inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-[#607086] transition hover:bg-[#f3f7f6] hover:text-[#0e7c86]"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Use a different phone number
            </Link>
        </div>
    </CustomerPortalLayout>
</template>
