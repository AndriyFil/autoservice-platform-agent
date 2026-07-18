<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CustomerPortalLayout from '@/layouts/CustomerPortalLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, Clock3, LoaderCircle, Phone } from 'lucide-vue-next';

defineProps<{
    sessionExpired?: boolean;
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();

const form = useForm({
    phone: '',
});

const submit = () => {
    form.post(route('customer-portal.access.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Access your service requests" />

    <CustomerPortalLayout :can-login="canLogin" :can-register="canRegister" :admin-login-url="adminLoginUrl" :admin-register-url="adminRegisterUrl">
        <div class="flex size-12 items-center justify-center rounded-2xl bg-[#e9f3f2] text-[#0e7c86]">
            <Phone class="size-5" aria-hidden="true" />
        </div>

        <div class="mt-6">
            <p class="public-kicker">Customer portal</p>
            <h1 class="mt-2 text-[1.75rem] font-bold leading-tight tracking-[-0.035em] text-[#0b1f33]">Access your service requests</h1>
            <p class="mt-3 text-[0.95rem] leading-6 text-[#607086]">Enter your phone number. We’ll send a short one-time code to verify access.</p>
        </div>

        <div
            v-if="sessionExpired"
            class="mt-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
            aria-live="polite"
            role="status"
        >
            <Clock3 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <p>Your verification session expired. Enter your phone number to request a new code.</p>
        </div>

        <form class="mt-7 space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="phone">Phone number</Label>
                <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    name="phone"
                    autocomplete="tel"
                    placeholder="+38 050 123 45 67"
                    required
                    aria-describedby="phone-help phone-error"
                    :aria-invalid="form.errors.phone ? true : undefined"
                    class="public-field"
                />
                <p id="phone-help" class="text-xs leading-5 text-slate-500">Use the number you shared with the workshop.</p>
                <div id="phone-error" aria-live="polite">
                    <InputError :message="form.errors.phone" />
                </div>
            </div>

            <Button type="submit" class="public-button-primary min-h-12 w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                <ArrowRight v-else class="size-4" aria-hidden="true" />
                Send verification code
            </Button>
        </form>

        <p class="mt-5 text-center text-xs leading-5 text-slate-500">The code only confirms access to this phone number and expires shortly.</p>
    </CustomerPortalLayout>
</template>
