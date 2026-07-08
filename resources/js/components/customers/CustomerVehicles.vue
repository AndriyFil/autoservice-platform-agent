<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Save, X } from 'lucide-vue-next';
import { ref } from 'vue';
import type { CustomerVehicle } from './types';
import { vehicleSummary } from './utils';

const props = defineProps<{
    customerId: number;
    vehicles: CustomerVehicle[];
}>();

const addForm = useForm({
    make: '',
    model: '',
    year: null as number | null,
    plate: '',
});

const editingVehicleId = ref<number | null>(null);
const editForm = useForm({
    make: '',
    model: '',
    year: null as number | null,
    plate: '',
});

const submitAddVehicle = () => {
    addForm.post(route('customers.vehicles.store', { customer: props.customerId }), {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
};

const startEdit = (vehicle: CustomerVehicle) => {
    editingVehicleId.value = vehicle.id;
    editForm.make = vehicle.brand ?? '';
    editForm.model = vehicle.model ?? '';
    editForm.year = vehicle.year;
    editForm.plate = vehicle.licensePlate ?? '';
};

const cancelEdit = () => {
    editingVehicleId.value = null;
    editForm.clearErrors();
    editForm.reset();
};

const submitEditVehicle = (vehicle: CustomerVehicle) => {
    editForm.patch(route('customers.vehicles.update', { customer: props.customerId, vehicle: vehicle.id }), {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
};
</script>

<template>
    <section class="space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex items-center justify-between gap-3 border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Vehicles</h2>
        </div>

        <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitAddVehicle">
            <div class="space-y-1">
                <Label for="vehicle-make">Make</Label>
                <Input id="vehicle-make" v-model="addForm.make" autocomplete="off" />
                <InputError :message="addForm.errors.make" />
            </div>

            <div class="space-y-1">
                <Label for="vehicle-model">Model</Label>
                <Input id="vehicle-model" v-model="addForm.model" autocomplete="off" />
                <InputError :message="addForm.errors.model" />
            </div>

            <div class="space-y-1">
                <Label for="vehicle-year">Year</Label>
                <Input id="vehicle-year" v-model.number="addForm.year" type="number" min="1900" />
                <InputError :message="addForm.errors.year" />
            </div>

            <div class="space-y-1">
                <Label for="vehicle-plate">Plate</Label>
                <Input id="vehicle-plate" v-model="addForm.plate" autocomplete="off" />
                <InputError :message="addForm.errors.plate" />
            </div>

            <div class="sm:col-span-2">
                <Button type="submit" size="sm" :disabled="addForm.processing">
                    <Plus class="size-4" />
                    Add vehicle
                </Button>
            </div>
        </form>

        <div v-if="vehicles.length === 0" class="text-sm text-muted-foreground">No vehicles yet.</div>

        <div v-else class="space-y-3">
            <div v-for="vehicle in vehicles" :key="vehicle.id" class="rounded-md border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                <div class="flex items-start justify-between gap-3">
                    <div class="text-sm font-medium text-foreground">{{ vehicleSummary(vehicle) }}</div>
                    <Button type="button" size="icon" variant="ghost" @click="startEdit(vehicle)">
                        <Pencil class="size-4" />
                    </Button>
                </div>

                <form
                    v-if="editingVehicleId === vehicle.id"
                    class="mt-3 grid gap-3 text-sm sm:grid-cols-2"
                    @submit.prevent="submitEditVehicle(vehicle)"
                >
                    <div class="space-y-1">
                        <Label :for="`edit-vehicle-make-${vehicle.id}`">Make</Label>
                        <Input :id="`edit-vehicle-make-${vehicle.id}`" v-model="editForm.make" autocomplete="off" />
                        <InputError :message="editForm.errors.make" />
                    </div>

                    <div class="space-y-1">
                        <Label :for="`edit-vehicle-model-${vehicle.id}`">Model</Label>
                        <Input :id="`edit-vehicle-model-${vehicle.id}`" v-model="editForm.model" autocomplete="off" />
                        <InputError :message="editForm.errors.model" />
                    </div>

                    <div class="space-y-1">
                        <Label :for="`edit-vehicle-year-${vehicle.id}`">Year</Label>
                        <Input :id="`edit-vehicle-year-${vehicle.id}`" v-model.number="editForm.year" type="number" min="1900" />
                        <InputError :message="editForm.errors.year" />
                    </div>

                    <div class="space-y-1">
                        <Label :for="`edit-vehicle-plate-${vehicle.id}`">Plate</Label>
                        <Input :id="`edit-vehicle-plate-${vehicle.id}`" v-model="editForm.plate" autocomplete="off" />
                        <InputError :message="editForm.errors.plate" />
                    </div>

                    <div class="flex gap-2 sm:col-span-2">
                        <Button type="submit" size="sm" :disabled="editForm.processing">
                            <Save class="size-4" />
                            Save
                        </Button>
                        <Button type="button" size="sm" variant="outline" @click="cancelEdit">
                            <X class="size-4" />
                            Cancel
                        </Button>
                    </div>
                </form>

                <dl v-else class="mt-3 grid gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium uppercase text-muted-foreground">Make</dt>
                        <dd class="mt-1 text-foreground">{{ vehicle.brand ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-muted-foreground">Model</dt>
                        <dd class="mt-1 text-foreground">{{ vehicle.model ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-muted-foreground">Year</dt>
                        <dd class="mt-1 text-foreground">{{ vehicle.year ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-muted-foreground">Plate</dt>
                        <dd class="mt-1 text-foreground">{{ vehicle.licensePlate ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</template>
