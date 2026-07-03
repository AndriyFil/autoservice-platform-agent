<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Save, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';
import type { RepairOrderLine, RepairOrderLineTypeOption } from './types';
import { centsToDecimalInput, decimalInputToCents, formatCents } from './utils';

type LineForm = {
    type: RepairOrderLineTypeOption['value'];
    description: string;
    quantity: string;
    unit_price: string;
    tax_rate: string;
    sort_order: number | null;
};

type LineSubmission = Omit<LineForm, 'unit_price'> & {
    unit_price_cents: number;
};

const props = defineProps<{
    repairOrderId: number;
    lines: RepairOrderLine[];
    availableLineTypes: RepairOrderLineTypeOption[];
}>();
const { t } = useTranslations();

const editingLineId = ref<number | null>(null);

const blankForm = (): LineForm => ({
    type: props.availableLineTypes[0]?.value ?? 'labor',
    description: '',
    quantity: '1.00',
    unit_price: '0.00',
    tax_rate: '0.00',
    sort_order: null,
});

const addForm = useForm<LineForm>(blankForm());
const editForm = useForm<LineForm>(blankForm());
const deleteForm = useForm({});

const toSubmission = (form: LineForm): LineSubmission => ({
    type: form.type,
    description: form.description,
    quantity: form.quantity,
    unit_price_cents: decimalInputToCents(form.unit_price),
    tax_rate: form.tax_rate,
    sort_order: form.sort_order,
});

const resetAddForm = () => {
    addForm.defaults(blankForm());
    addForm.reset();
    addForm.clearErrors();
};

const submitAddLine = () => {
    addForm
        .transform((data) => toSubmission(data))
        .post(route('dashboard.repair-orders.lines.store', { repairOrder: props.repairOrderId }), {
            preserveScroll: true,
            onSuccess: resetAddForm,
        });
};

const startEditing = (line: RepairOrderLine) => {
    editingLineId.value = line.id;
    editForm.type = line.type.value;
    editForm.description = line.description;
    editForm.quantity = line.quantity;
    editForm.unit_price = centsToDecimalInput(line.unitPriceCents);
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
    editForm
        .transform((data) => toSubmission(data))
        .patch(
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
            <h2 class="text-base font-semibold text-foreground">{{ t('repair_orders.sections.working_lines') }}</h2>
            <div class="text-sm text-muted-foreground">
                {{ lines.length }} {{ t(lines.length === 1 ? 'repair_orders.units.line_singular' : 'repair_orders.units.line_plural') }}
            </div>
        </div>

        <form
            class="grid gap-3 rounded-md border border-sidebar-border/70 bg-muted/30 p-3 dark:border-sidebar-border"
            @submit.prevent="submitAddLine"
        >
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[8rem_minmax(12rem,1fr)_7rem_9rem_7rem_6rem]">
                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">{{ t('repair_orders.fields.type') }}</span>
                    <select v-model="addForm.type" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="type in availableLineTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <InputError :message="addForm.errors.type" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">{{ t('repair_orders.fields.description') }}</span>
                    <input v-model="addForm.description" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" type="text" />
                    <InputError :message="addForm.errors.description" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">{{ t('repair_orders.fields.quantity') }}</span>
                    <input
                        v-model="addForm.quantity"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        type="number"
                        min="0.01"
                        step="0.01"
                    />
                    <InputError :message="addForm.errors.quantity" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">{{ t('repair_orders.fields.unit_price') }}</span>
                    <input
                        v-model="addForm.unit_price"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                    />
                    <InputError :message="addForm.errors.unit_price_cents" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">{{ t('repair_orders.fields.tax') }} %</span>
                    <input
                        v-model="addForm.tax_rate"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                    />
                    <InputError :message="addForm.errors.tax_rate" />
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-foreground">{{ t('repair_orders.fields.order') }}</span>
                    <input
                        v-model.number="addForm.sort_order"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        type="number"
                        min="0"
                        step="1"
                    />
                    <InputError :message="addForm.errors.sort_order" />
                </label>
            </div>

            <div class="flex justify-end">
                <Button type="submit" size="sm" :disabled="addForm.processing">
                    <Plus class="size-4" />
                    {{ t('repair_orders.actions.add_line') }}
                </Button>
            </div>
        </form>

        <div
            v-if="lines.length === 0"
            class="rounded-md border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            {{ t('repair_orders.messages.no_working_lines') }}
        </div>

        <div v-else class="overflow-x-auto rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead
                    class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.type') }}</th>
                        <th class="px-3 py-2">{{ t('repair_orders.fields.description') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('repair_orders.fields.quantity') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('repair_orders.fields.unit') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('repair_orders.fields.tax') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('repair_orders.fields.total') }}</th>
                        <th class="w-36 px-3 py-2 text-right">{{ t('repair_orders.fields.actions') }}</th>
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
                                <input
                                    v-model="editForm.description"
                                    class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                                    type="text"
                                />
                                <InputError :message="editForm.errors.description" />
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input
                                    v-model="editForm.quantity"
                                    class="h-9 w-24 rounded-md border border-input bg-background px-2 text-right text-sm"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                />
                                <InputError :message="editForm.errors.quantity" />
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input
                                    v-model="editForm.unit_price"
                                    class="h-9 w-28 rounded-md border border-input bg-background px-2 text-right text-sm"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                                />
                                <InputError :message="editForm.errors.unit_price_cents" />
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input
                                    v-model="editForm.tax_rate"
                                    class="h-9 w-24 rounded-md border border-input bg-background px-2 text-right text-sm"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                />
                                <InputError :message="editForm.errors.tax_rate" />
                            </td>
                            <td class="px-3 py-2 text-right align-top text-muted-foreground">{{ formatCents(line.totalCents) }}</td>
                            <td class="px-3 py-2 align-top">
                                <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                    <Button
                                        type="button"
                                        size="sm"
                                        class="w-full sm:w-auto"
                                        :disabled="editForm.processing"
                                        @click="submitEditLine(line)"
                                    >
                                        <Save class="size-4" />
                                        {{ t('repair_orders.actions.save') }}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="w-full sm:w-auto"
                                        :disabled="editForm.processing"
                                        @click="cancelEditing"
                                    >
                                        <X class="size-4" />
                                        {{ t('repair_orders.actions.cancel') }}
                                    </Button>
                                </div>
                            </td>
                        </template>

                        <template v-else>
                            <td class="whitespace-nowrap px-3 py-2 align-top text-muted-foreground">{{ line.type.label }}</td>
                            <td class="px-3 py-2 align-top text-foreground">{{ line.description }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-muted-foreground">{{ line.quantity }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-muted-foreground">
                                {{ formatCents(line.unitPriceCents) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-muted-foreground">{{ line.taxRate }}%</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top font-medium text-foreground">
                                {{ formatCents(line.totalCents) }}
                            </td>
                            <td class="px-3 py-2 align-top">
                                <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="w-full sm:w-auto"
                                        :disabled="deleteForm.processing"
                                        @click="startEditing(line)"
                                    >
                                        <Pencil class="size-4" />
                                        {{ t('repair_orders.actions.edit') }}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        class="w-full sm:w-auto"
                                        :disabled="deleteForm.processing"
                                        @click="deleteLine(line)"
                                    >
                                        <Trash2 class="size-4" />
                                        {{ t('repair_orders.actions.delete') }}
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
