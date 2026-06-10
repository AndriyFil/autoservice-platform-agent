<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { CalendarDays, Car, Phone, Send, User, Wrench } from 'lucide-vue-next';

const props = defineProps<{
    workshop: {
        name: string;
        slug: string;
    };
}>();

const form = useForm({
    customer_name: '',
    customer_phone: '',
    problem_description: '',
    preferred_date: '',
    vehicle: {
        brand: '',
        model: '',
        license_plate: '',
    },
});

const submit = () => {
    form.post(route('public-booking-requests.store', props.workshop.slug), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Book ${workshop.name}`" />

    <main class="min-h-screen bg-background px-6 py-10 text-foreground">
        <section class="mx-auto flex w-full max-w-2xl flex-col gap-8">
            <header class="space-y-4">
                <div class="flex size-12 items-center justify-center rounded-md border bg-muted">
                    <Wrench class="size-6" />
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">{{ workshop.name }}</p>
                    <h1 class="text-3xl font-semibold tracking-normal">Request a booking</h1>
                    <p class="text-sm leading-6 text-muted-foreground">
                        Send your contact details and repair request. The workshop will contact you directly.
                    </p>
                </div>
            </header>

            <form class="space-y-8" @submit.prevent="submit">
                <section class="space-y-5">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <User class="size-4" />
                        Contact
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="customer_name">Name</Label>
                            <Input
                                id="customer_name"
                                v-model="form.customer_name"
                                type="text"
                                name="customer_name"
                                autocomplete="name"
                                required
                            />
                            <InputError :message="form.errors.customer_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer_phone">Phone</Label>
                            <div class="relative">
                                <Phone class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="customer_phone"
                                    v-model="form.customer_phone"
                                    class="pl-9"
                                    type="tel"
                                    name="customer_phone"
                                    autocomplete="tel"
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.customer_phone" />
                        </div>
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <Wrench class="size-4" />
                        Request
                    </div>

                    <div class="space-y-2">
                        <Label for="problem_description">Problem description</Label>
                        <textarea
                            id="problem_description"
                            v-model="form.problem_description"
                            name="problem_description"
                            rows="5"
                            required
                            class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        />
                        <InputError :message="form.errors.problem_description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="preferred_date">Preferred date</Label>
                        <div class="relative max-w-xs">
                            <CalendarDays class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="preferred_date"
                                v-model="form.preferred_date"
                                class="pl-9"
                                type="date"
                                name="preferred_date"
                            />
                        </div>
                        <InputError :message="form.errors.preferred_date" />
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <Car class="size-4" />
                        Vehicle
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="vehicle_brand">Brand</Label>
                            <Input id="vehicle_brand" v-model="form.vehicle.brand" type="text" name="vehicle[brand]" />
                            <InputError :message="form.errors['vehicle.brand']" />
                        </div>

                        <div class="space-y-2">
                            <Label for="vehicle_model">Model</Label>
                            <Input id="vehicle_model" v-model="form.vehicle.model" type="text" name="vehicle[model]" />
                            <InputError :message="form.errors['vehicle.model']" />
                        </div>

                        <div class="space-y-2">
                            <Label for="vehicle_license_plate">License plate</Label>
                            <Input
                                id="vehicle_license_plate"
                                v-model="form.vehicle.license_plate"
                                type="text"
                                name="vehicle[license_plate]"
                            />
                            <InputError :message="form.errors['vehicle.license_plate']" />
                        </div>
                    </div>
                </section>

                <Button class="w-full sm:w-auto" type="submit" :disabled="form.processing">
                    <Send class="size-4" />
                    Send request
                </Button>
            </form>
        </section>
    </main>
</template>
