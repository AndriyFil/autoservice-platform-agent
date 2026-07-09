<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { WorkshopSettings } from '@/pages/Dashboard/Workshop/type';
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

const props = defineProps<{
    workshop: WorkshopSettings;
}>();

const form = useForm({
    name: props.workshop.name,
    slug: props.workshop.slug,
});

const submit = () => {
    form.patch(route('dashboard.workshop.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section class="max-w-3xl space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Basic settings</h2>
        </div>

        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
            <div class="space-y-1">
                <Label for="workshop-name">Workshop name</Label>
                <Input id="workshop-name" v-model="form.name" autocomplete="organization" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-1">
                <Label for="workshop-slug">Slug</Label>
                <Input id="workshop-slug" v-model="form.slug" autocomplete="off" />
                <InputError :message="form.errors.slug" />
            </div>

            <div class="sm:col-span-2">
                <div class="text-xs font-medium uppercase text-muted-foreground">Created</div>
                <div class="mt-1 text-sm text-foreground">{{ new Date(workshop.createdAt).toLocaleString() }}</div>
            </div>

            <div class="sm:col-span-2">
                <Button type="submit" size="sm" :disabled="form.processing">
                    <Save class="size-4" />
                    Save settings
                </Button>
            </div>
        </form>
    </section>
</template>
