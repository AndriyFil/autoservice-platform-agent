<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Save, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';
import type { RepairOrderLine, RepairOrderLineTypeOption } from './types';
import { formatCents } from './utils';

type LineForm = {
    type: RepairOrderLineTypeOption['value'];
    description: string;
    quantity: string;
    unit_price_cents: number;
    tax_rate: string;
    sort_order: number | null;
};

const props = defineProps<{
    repairOrderId: number;
    lines: RepairOrderLine[];
    availableLineTypes: RepairOrderLineTypeOption[];
}>();

const editingLineId = ref<number | null>(null);

const blankForm = (): LineForm => ({
    type: props.availableLineTypes[0]?.value ?? 'labor',
    description: '',
    quantity: '1.00',
    unit_price_cents: 0,
    tax_rate: '0.00',
    sort_order: null,
});

const addForm = useForm<LineForm>(blankForm());
const editForm = useForm<LineForm>(blankForm());
const deleteForm = useForm({});

const resetAddForm = () => {
    addForm.defaults(blankForm());
    addForm.reset();
    addForm.clearErrors();
};

const submitAddLine = () => {
    addForm.post(route('dashboard.repair-orders.lines.store', { repairOrder: props.repairOrderId }), {
        preserveScroll: true,
        onSuccess: resetAddForm,
    });
};

const startEditing = (line: RepairOrderLine) => {
    editingLineId.value = line.id;
    editForm.type = line.type.value;
    editForm.description = line.description;
    editForm.quantity = line.quantity;
    editForm.unit_price_cents = line.unitPriceCents;
    editForm.tax_rate = line.taxRate;
    editForm.sort_order = line.sortOrder;
    editForm.clearErrors();
};

const cancelEditing = () => {
    editingLineId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEditLine = (line: RepairOrderLine) => {
    editForm.patch(
        route('dashboard.repair-orders.lines.update', {
            repairOrder: props.repairOrderId,
            repairOrderLine: line.id,
        }),
        {
            preserveScroll: true,
            onSuccess: cancelEditing,
        },
    );
};

const deleteLine = (line: RepairOrderLine) => {
    deleteForm.delete(
        route('dashboard.repair-orders.lines.destroy', {
            repairOrder: props.repairOrderId,
            repairOrderLine: line.id,
        }),
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-foreground">Estimate lines</h2>
            <div class="text-sm text-muted-foreground">{{ lines.length }} line{{ lines.length === 1 ? '' : 's' }}</div>
        </div>

        <form class="grid gap-3 rounded-md border border-sidebar-border/70 bg-muted/30 p-3 dark:border-sidebar-border" @submit.prevent="submitAddLine">
            <div class="grid gap-3 lg:grid-cols-[8rem_minmax(0,1fr)_7rem_9rem_7rem_6rem]">
                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">Type</span>
                    <select v-model="addForm.type" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="type in availableLineTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <InputError :message="addForm.errors.type" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">Description</span>
                    <input v-model="addForm.description" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" type="text" />
                    <InputError :message="addForm.errors.description" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">Qty</span>
                    <input v-model="addForm.quantity" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" type="number" min="0.01" step="0.01" />
                    <InputError :message="addForm.errors.quantity" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">Unit cents</span>
                    <input v-model.number="addForm.unit_price_cents" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" type="number" min="0" step="1" />
                    <InputError :message="addForm.errors.unit_price_cents" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">Tax %</span>
                    <input v-model="addForm.tax_rate" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" type="number" min="0" max="100" step="0.01" />
                    <InputError :message="addForm.errors.tax_rate" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">Order</span>
                    <input v-model.number="addForm.sort_order" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" type="number" min="0" step="1" />
                    <InputError :message="addForm.errors.sort_order" />
                </label>
            </div>

            <div class="flex justify-end">
                <Button type="submit" size="sm" :disabled="addForm.processing">
                    <Plus class="size-4" />
                    Add line
                </Button>
            </div>
        </form>

        <div v-if="lines.length === 0" class="rounded-md border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
            No estimate lines yet.
        </div>

        <div v-else class="overflow-x-auto rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border">
                    <tr>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Description</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Unit</th>
                        <th class="px-3 py-2 text-right">Tax</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="line in lines" :key="line.id">
                        <template v-if="editingLineId === line.id">
                            <td class="px-3 py-2 align-top">
                                <select v-model="editForm.type" class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                                    <option v-for="type in availableLineTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                </select>
                            </td>
                            <td class="min-w-56 px-3 py-2 align-top">
                                <input v-model="editForm.description" class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm" type="text" />
                                <InputError :message="editForm.errors.description" />
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input v-model="editForm.quantity" class="h-9 w-24 rounded-md border border-input bg-background px-2 text-right text-sm" type="number" min="0.01" step="0.01" />
                                <InputError :message="editForm.errors.quantity" />
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input v-model.number="editForm.unit_price_cents" class="h-9 w-28 rounded-md border border-input bg-background px-2 text-right text-sm" type="number" min="0" step="1" />
                                <InputError :message="editForm.errors.unit_price_cents" />
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input v-model="editForm.tax_rate" class="h-9 w-24 rounded-md border border-input bg-background px-2 text-right text-sm" type="number" min="0" max="100" step="0.01" />
                                <InputError :message="editForm.errors.tax_rate" />
                            </td>
                            <td class="px-3 py-2 text-right align-top text-muted-foreground">{{ formatCents(line.totalCents) }}</td>
                            <td class="px-3 py-2 align-top">
                                <div class="flex justify-end gap-2">
                                    <Button type="button" size="sm" :disabled="editForm.processing" @click="submitEditLine(line)">
                                        <Save class="size-4" />
                                        Save
                                    </Button>
                                    <Button type="button" size="sm" variant="outline" :disabled="editForm.processing" @click="cancelEditing">
                                        <X class="size-4" />
                                        Cancel
                                    </Button>
                                </div>
                            </td>
                        </template>

                        <template v-else>
                            <td class="whitespace-nowrap px-3 py-2 align-top text-muted-foreground">{{ line.type.label }}</td>
                            <td class="px-3 py-2 align-top text-foreground">{{ line.description }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-muted-foreground">{{ line.quantity }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-muted-foreground">{{ formatCents(line.unitPriceCents) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-muted-foreground">{{ line.taxRate }}%</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top font-medium text-foreground">{{ formatCents(line.totalCents) }}</td>
                            <td class="px-3 py-2 align-top">
                                <div class="flex justify-end gap-2">
                                    <Button type="button" size="sm" variant="outline" :disabled="deleteForm.processing" @click="startEditing(line)">
                                        <Pencil class="size-4" />
                                        Edit
                                    </Button>
                                    <Button type="button" size="sm" variant="destructive" :disabled="deleteForm.processing" @click="deleteLine(line)">
                                        <Trash2 class="size-4" />
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
