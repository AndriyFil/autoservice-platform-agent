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
        <div class="flex size-11 items-center justify-center rounded-lg bg-[#2f6471]/10 text-[#2f6471]">
            <KeyRound class="size-5" aria-hidden="true" />
        </div>

        <div class="mt-6">
            <p class="text-sm font-medium text-[#2f6471]">Phone verification</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Check your phone</h1>
            <p id="code-help" class="mt-3 text-sm leading-6 text-slate-600">
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
                    class="h-12 text-center font-mono text-lg tracking-[0.35em]"
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

            <Button type="submit" class="min-h-11 w-full bg-[#2f6471] font-semibold text-white hover:bg-[#285864]" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                <Check v-else class="size-4" aria-hidden="true" />
                Verify phone
            </Button>
        </form>

        <div class="mt-5 text-center">
            <Link
                :href="route('customer-portal.access.create')"
                class="inline-flex min-h-10 items-center gap-2 rounded-md px-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Use a different phone number
            </Link>
        </div>
    </CustomerPortalLayout>
</template>
