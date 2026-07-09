<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { WorkshopSettings } from '@/pages/Dashboard/Workshop/type';
import { Check, Clipboard } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    workshop: WorkshopSettings;
}>();

const copied = ref(false);

const displayUrl = computed(() => props.workshop.publicIntakeUrl || props.workshop.publicIntakePath);

const copyLink = async () => {
    if (!navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(displayUrl.value);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 1800);
};
</script>

<template>
    <section class="max-w-3xl space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Public customer intake link</h2>
            <p class="mt-1 text-sm text-muted-foreground">Share this URL with customers so new requests arrive on the public workshop intake page.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div
                class="min-w-0 flex-1 rounded-md border border-sidebar-border/70 bg-muted/30 px-3 py-2 text-sm text-foreground dark:border-sidebar-border"
            >
                <span class="block truncate">{{ displayUrl }}</span>
            </div>

            <Button type="button" size="sm" variant="outline" @click="copyLink">
                <Check v-if="copied" class="size-4" />
                <Clipboard v-else class="size-4" />
                {{ copied ? 'Copied' : 'Copy' }}
            </Button>
        </div>
    </section>
</template>
