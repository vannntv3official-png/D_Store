<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Dialog from '@/Components/Dialog.vue';
import Badge from '@/Components/Badge.vue';
import { UserPlus, Shield, Power, Edit3 } from 'lucide-vue-next';

interface StaffRole {
    id: number;
    name: string;
    display_name: string;
}

interface StaffMember {
    id: number;
    name: string;
    email: string;
    phone: string;
    is_active: boolean;
    roles: StaffRole[];
    created_at: string;
}

interface RoleOption {
    id: number;
    name: string;
    display_name: string;
}

interface Props {
    staffMembers: StaffMember[];
    roles: RoleOption[];
}

const props = defineProps<Props>();

// Create Staff Modal
const showCreateModal = ref(false);
const isSubmittingCreate = ref(false);
const createErrors = ref<Record<string, string>>({});
const createForm = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    role_ids: [] as number[],
});

// Edit Roles Modal
const showEditRolesModal = ref(false);
const editingStaff = ref<StaffMember | null>(null);
const isSubmittingRoles = ref(false);
const editRoles = ref<number[]>([]);
const rolesError = ref('');

const openCreate = () => {
    createForm.name = '';
    createForm.email = '';
    createForm.phone = '';
    createForm.password = '';
    createForm.role_ids = props.roles.length > 0 ? [props.roles[0].id] : [];
    createErrors.value = {};
    showCreateModal.value = true;
};

const submitCreate = () => {
    isSubmittingCreate.value = true;
    createErrors.value = {};

    router.post('/admin/staff', createForm, {
        onSuccess: () => {
            showCreateModal.value = false;
        },
        onError: (err) => {
            createErrors.value = err;
        },
        onFinish: () => {
            isSubmittingCreate.value = false;
        },
    });
};

const openEditRoles = (member: StaffMember) => {
    editingStaff.value = member;
    editRoles.value = member.roles.map((r) => r.id);
    rolesError.value = '';
    showEditRolesModal.value = true;
};

const submitEditRoles = () => {
    if (!editingStaff.value) return;
    isSubmittingRoles.value = true;
    rolesError.value = '';

    router.put(`/admin/staff/${editingStaff.value.id}/roles`, { role_ids: editRoles.value }, {
        onSuccess: () => {
            showEditRolesModal.value = false;
        },
        onError: (err) => {
            rolesError.value = err.roles || 'Failed to update roles.';
        },
        onFinish: () => {
            isSubmittingRoles.value = false;
        },
    });
};

const toggleStaffActive = (id: number) => {
    router.post(`/admin/staff/${id}/toggle-active`);
};
</script>

<template>
    <AdminLayout>
        <Head title="Staff & Roles Access — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Staff & Administrative Roles</h1>
                    <p class="text-xs text-[#86868B] mt-1">Role-based access control (RBAC), permission boundaries, and audit identities.</p>
                </div>
                <Button variant="primary" size="sm" @click="openCreate" class="flex items-center gap-1.5">
                    <UserPlus class="w-3.5 h-3.5" />
                    <span>Add Staff Member</span>
                </Button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Staff Member</th>
                                <th class="py-3.5 px-6">Contact / Phone</th>
                                <th class="py-3.5 px-6">Assigned Roles</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6">Member Since</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr
                                v-for="member in staffMembers"
                                :key="member.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-medium">
                                    <div class="font-bold text-[#111111]">{{ member.name }}</div>
                                    <div class="text-[11px] text-[#86868B]">{{ member.email }}</div>
                                </td>
                                <td class="py-4 px-6 text-[#1D1D1F] font-mono">
                                    {{ member.phone }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="r in member.roles"
                                            :key="r.id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#111111] text-white"
                                        >
                                            <Shield class="w-3 h-3 text-amber-400" />
                                            {{ r.display_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="member.is_active ? 'success' : 'danger'">
                                        {{ member.is_active ? 'Active' : 'Disabled' }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6 text-[#86868B]">
                                    {{ member.created_at }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditRoles(member)"
                                            class="p-1.5 rounded-lg text-[#86868B] hover:text-[#111111] hover:bg-[#F5F5F7] transition-colors"
                                            title="Edit Roles"
                                        >
                                            <Edit3 class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            @click="toggleStaffActive(member.id)"
                                            class="p-1.5 rounded-lg text-[#86868B] hover:text-[#DC2626] hover:bg-[#F5F5F7] transition-colors"
                                            :title="member.is_active ? 'Deactivate account' : 'Activate account'"
                                        >
                                            <Power class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Staff Dialog -->
        <Dialog :open="showCreateModal" @close="showCreateModal = false" title="Provision Staff Account">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="createForm.name"
                        label="Full Name"
                        placeholder="e.g. Dara Meng"
                        :error="createErrors.name"
                        required
                    />
                    <Input
                        v-model="createForm.phone"
                        label="Phone Number"
                        placeholder="e.g. 012 999 888"
                        :error="createErrors.phone"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="createForm.email"
                        type="email"
                        label="Company Email"
                        placeholder="dara@dstore.com.kh"
                        :error="createErrors.email"
                        required
                    />
                    <Input
                        v-model="createForm.password"
                        type="password"
                        label="Password"
                        placeholder="••••••••"
                        :error="createErrors.password"
                        required
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#111111] mb-2">Assign Roles</label>
                    <div class="grid grid-cols-2 gap-2 p-3 bg-[#FBFBFC] rounded-xl border border-[#E5E5EA]">
                        <label
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center gap-2 text-xs text-[#1D1D1F] cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="role.id"
                                v-model="createForm.role_ids"
                                class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                            />
                            <span>{{ role.display_name }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showCreateModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" :loading="isSubmittingCreate">
                        Create Staff
                    </Button>
                </div>
            </form>
        </Dialog>

        <!-- Edit Roles Dialog -->
        <Dialog :open="showEditRolesModal" @close="showEditRolesModal = false" title="Modify Staff Roles">
            <div v-if="editingStaff" class="space-y-4">
                <div class="p-3 bg-[#FBFBFC] rounded-xl border border-[#E5E5EA] text-xs">
                    <p class="font-bold text-[#111111]">{{ editingStaff.name }}</p>
                    <p class="text-[#86868B]">{{ editingStaff.email }}</p>
                </div>

                <div v-if="rolesError" class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs font-medium">
                    {{ rolesError }}
                </div>

                <form @submit.prevent="submitEditRoles" class="space-y-4">
                    <div class="grid grid-cols-1 gap-2 p-3 bg-[#FBFBFC] rounded-xl border border-[#E5E5EA]">
                        <label
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center gap-2 text-xs text-[#1D1D1F] cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="role.id"
                                v-model="editRoles"
                                class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                            />
                            <span>{{ role.display_name }} ({{ role.name }})</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                        <Button type="button" variant="secondary" @click="showEditRolesModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" :loading="isSubmittingRoles">
                            Save Roles
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
    </AdminLayout>
</template>
