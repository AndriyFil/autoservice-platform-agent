<script setup lang="ts">
import type { CustomerVehicle } from './types';
import { vehicleSummary } from './utils';

defineProps<{
    vehicles: CustomerVehicle[];
}>();
</script>

<template>
    <section class="space-y-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h2 class="text-base font-semibold text-foreground">Vehicles</h2>

        <div v-if="vehicles.length === 0" class="text-sm text-muted-foreground">No vehicles yet.</div>

        <div v-else class="space-y-3">
            <div v-for="vehicle in vehicles" :key="vehicle.id" class="rounded-md border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                <div class="text-sm font-medium text-foreground">{{ vehicleSummary(vehicle) }}</div>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium uppercase text-muted-foreground">Brand</dt>
                        <dd class="mt-1 text-foreground">{{ vehicle.brand ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-muted-foreground">Model</dt>
                        <dd class="mt-1 text-foreground">{{ vehicle.model ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-muted-foreground">License plate</dt>
                        <dd class="mt-1 text-foreground">{{ vehicle.licensePlate ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</template>
