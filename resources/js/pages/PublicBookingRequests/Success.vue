<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Plus } from 'lucide-vue-next';

const props = defineProps<{
    workshop: {
        name: string;
        slug: string;
    };
}>();
</script>

<template>
    <Head title="Request sent" />

    <main class="flex min-h-screen items-center justify-center bg-background px-6 py-12 text-foreground">
        <section class="w-full max-w-md space-y-8">
            <div class="space-y-4">
                <div class="flex size-12 items-center justify-center rounded-md border bg-muted text-foreground">
                    <CheckCircle2 class="size-6" />
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">{{ workshop.name }}</p>
                    <h1 class="text-2xl font-semibold tracking-normal">Request sent</h1>
                    <p class="text-sm leading-6 text-muted-foreground">
                        The workshop received your request and will contact you directly.
                    </p>
                </div>
            </div>

            <Button as-child class="w-full">
                <Link :href="route('public-booking-requests.create', props.workshop.slug)">
                    <Plus class="size-4" />
                    Send another request
                </Link>
            </Button>
        </section>
    </main>
</template>
