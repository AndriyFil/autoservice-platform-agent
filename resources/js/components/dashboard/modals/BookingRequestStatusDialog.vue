<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { PendingStatusChange } from '../types';

defineProps<{
    pendingStatusChange: PendingStatusChange | null;
    processing: boolean;
}>();

defineEmits<{
    submit: [];
}>();

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ pendingStatusChange?.label }}</DialogTitle>
                <DialogDescription>
                    {{ pendingStatusChange?.description }}
                </DialogDescription>
            </DialogHeader>

            <div class="rounded-md border border-sidebar-border/70 bg-muted/40 px-3 py-2 text-sm text-foreground">
                {{ pendingStatusChange?.customerName }}
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Keep current status</Button>
                </DialogClose>

                <Button
                    type="button"
                    :variant="pendingStatusChange?.status === 'rejected' ? 'destructive' : 'default'"
                    :class="pendingStatusChange?.confirmButtonClass"
                    :disabled="processing"
                    @click="$emit('submit')"
                >
                    {{ pendingStatusChange?.label }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
