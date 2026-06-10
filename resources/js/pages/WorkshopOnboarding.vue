<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { Wrench } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const form = useForm({
    name: '',
    slug: '',
});

const slugManuallyEdited = ref(false);

const toSlug = (value: string) =>
    value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

watch(
    () => form.name,
    (name) => {
        if (!slugManuallyEdited.value) {
            form.slug = toSlug(name);
        }
    },
);

const updateSlug = (value: string) => {
    slugManuallyEdited.value = true;
    form.slug = toSlug(value);
};

const submit = () => {
    form.post(route('workshop-onboarding.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create workshop" />

    <main class="flex min-h-screen items-center justify-center bg-background px-6 py-12">
        <section class="w-full max-w-md space-y-8">
            <div class="space-y-4">
                <div class="flex size-12 items-center justify-center rounded-md border bg-muted">
                    <Wrench class="size-6" />
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold tracking-normal text-foreground">Create your workshop</h1>
                    <p class="text-sm leading-6 text-muted-foreground">
                        Add the workshop your team will use for customers, vehicles, and booking requests.
                    </p>
                </div>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="name">Workshop name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        name="name"
                        autocomplete="organization"
                        autofocus
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="slug">Workshop slug</Label>
                    <Input
                        id="slug"
                        :model-value="form.slug"
                        type="text"
                        name="slug"
                        autocomplete="off"
                        required
                        @update:model-value="updateSlug(String($event))"
                    />
                    <InputError :message="form.errors.slug" />
                </div>

                <Button class="w-full" type="submit" :disabled="form.processing">
                    Create workshop
                </Button>
            </form>

            <p class="text-center text-sm text-muted-foreground">
                Signed in with the wrong account?
                <TextLink :href="route('logout')" method="post" as="button">Log out</TextLink>
            </p>
        </section>
    </main>
</template>
