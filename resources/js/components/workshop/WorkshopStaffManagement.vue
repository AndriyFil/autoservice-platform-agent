<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RoleOption, StaffMember, WorkshopRoleValue } from '@/pages/Dashboard/Workshop/type';
import { useForm } from '@inertiajs/vue3';
import { Plus, Save, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    staffMembers: StaffMember[];
    roleOptions: RoleOption[];
}>();

const addForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff' as WorkshopRoleValue,
});

const roleForm = useForm({
    role: 'staff' as WorkshopRoleValue,
});

const removeForm = useForm({
    membership: '',
});

const selectedRoles = ref<Record<number, WorkshopRoleValue>>({});

watch(
    () => props.staffMembers,
    (members) => {
        selectedRoles.value = Object.fromEntries(members.map((member) => [member.id, member.role.value]));
    },
    { immediate: true },
);

const submitAdd = () => {
    addForm.post(route('dashboard.workshop.staff.store'), {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
};

const submitRole = (member: StaffMember) => {
    roleForm.role = selectedRoles.value[member.id] ?? member.role.value;

    roleForm.patch(route('dashboard.workshop.staff.update', { workshopUser: member.id }), {
        preserveScroll: true,
    });
};

const removeMember = (member: StaffMember) => {
    removeForm.delete(route('dashboard.workshop.staff.destroy', { workshopUser: member.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section class="space-y-5 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
            <h2 class="text-base font-semibold text-foreground">Staff members</h2>
        </div>

        <div>
            <h3 class="text-sm font-medium text-foreground">Create staff account</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                Create a login account for a staff member of this workshop. If this email already belongs to a platform user, they will be added to
                this workshop and their password will not be changed.
            </p>
        </div>

        <form class="grid gap-3 lg:grid-cols-2 xl:grid-cols-[1fr_1fr_180px_auto]" @submit.prevent="submitAdd">
            <div class="space-y-1">
                <Label for="staff-name">Name</Label>
                <Input id="staff-name" v-model="addForm.name" type="text" autocomplete="name" />
                <InputError :message="addForm.errors.name" />
            </div>

            <div class="space-y-1">
                <Label for="staff-email">Email</Label>
                <Input id="staff-email" v-model="addForm.email" type="email" autocomplete="email" />
                <InputError :message="addForm.errors.email" />
            </div>

            <div class="space-y-1">
                <Label for="staff-password">Password</Label>
                <Input id="staff-password" v-model="addForm.password" type="password" autocomplete="new-password" />
                <InputError :message="addForm.errors.password" />
            </div>

            <div class="space-y-1">
                <Label for="staff-password-confirmation">Confirm password</Label>
                <Input id="staff-password-confirmation" v-model="addForm.password_confirmation" type="password" autocomplete="new-password" />
                <InputError :message="addForm.errors.password_confirmation" />
            </div>

            <div class="space-y-1">
                <Label for="staff-role">Role</Label>
                <select
                    id="staff-role"
                    v-model="addForm.role"
                    class="shadow-xs flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                </select>
                <InputError :message="addForm.errors.role" />
            </div>

            <div class="flex items-end">
                <Button type="submit" size="sm" :disabled="addForm.processing">
                    <Plus class="size-4" />
                    Create staff
                </Button>
            </div>
        </form>

        <InputError :message="removeForm.errors.membership" />
        <InputError :message="roleForm.errors.role" />

        <div class="overflow-x-auto rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead
                    class="border-b border-sidebar-border/70 bg-muted/40 text-xs font-medium uppercase text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Role</th>
                        <th class="px-3 py-2">Joined</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="member in staffMembers"
                        :key="member.id"
                        class="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                    >
                        <td class="px-3 py-3">
                            <div class="font-medium text-foreground">{{ member.name }}</div>
                            <div v-if="member.isCurrentUser" class="text-xs text-muted-foreground">Current owner</div>
                        </td>
                        <td class="px-3 py-3 text-muted-foreground">{{ member.email }}</td>
                        <td class="px-3 py-3">
                            <form class="flex items-center gap-2" @submit.prevent="submitRole(member)">
                                <select
                                    v-model="selectedRoles[member.id]"
                                    class="shadow-xs h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    :disabled="member.isLastOwner"
                                >
                                    <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                                </select>
                                <Button type="submit" size="icon" variant="ghost" :disabled="member.isLastOwner || roleForm.processing">
                                    <Save class="size-4" />
                                </Button>
                            </form>
                        </td>
                        <td class="px-3 py-3 text-muted-foreground">{{ new Date(member.joinedAt).toLocaleDateString() }}</td>
                        <td class="px-3 py-3 text-right">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                :disabled="member.isLastOwner || removeForm.processing"
                                @click="removeMember(member)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
