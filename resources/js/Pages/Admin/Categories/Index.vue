<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import Textarea from '@/Components/Textarea.vue';
import Dialog from '@/Components/Dialog.vue';
import Badge from '@/Components/Badge.vue';
import { Plus, Edit3, Trash2, FolderTree } from 'lucide-vue-next';

interface CategoryItem {
    id: number;
    name_en: string;
    name_km: string;
    slug: string;
    parent_id: number | null;
    parent_name?: string | null;
    display_order: number;
    is_active: boolean;
    products_count: number;
}

interface Props {
    categories: CategoryItem[];
}

const props = defineProps<Props>();

const showModal = ref(false);
const editingCategory = ref<CategoryItem | null>(null);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const form = reactive({
    name_en: '',
    name_km: '',
    parent_id: '' as string | number,
    display_order: 0,
    is_active: true,
    description_en: '',
    description_km: '',
});

const parentOptions = computed(() => [
    { value: '', label: 'None (Top Level Root)' },
    ...props.categories
        .filter((c) => !editingCategory.value || c.id !== editingCategory.value.id)
        .map((c) => ({
            value: c.id,
            label: `${c.name_en} (${c.name_km || ''})`,
        })),
]);

const openCreate = () => {
    editingCategory.value = null;
    form.name_en = '';
    form.name_km = '';
    form.parent_id = '';
    form.display_order = props.categories.length + 1;
    form.is_active = true;
    form.description_en = '';
    form.description_km = '';
    errors.value = {};
    showModal.value = true;
};

const openEdit = (cat: CategoryItem) => {
    editingCategory.value = cat;
    form.name_en = cat.name_en;
    form.name_km = cat.name_km;
    form.parent_id = cat.parent_id || '';
    form.display_order = cat.display_order;
    form.is_active = cat.is_active;
    form.description_en = '';
    form.description_km = '';
    errors.value = {};
    showModal.value = true;
};

const submit = () => {
    isSubmitting.value = true;
    errors.value = {};

    const payload = {
        ...form,
        parent_id: form.parent_id ? Number(form.parent_id) : null,
    };

    if (editingCategory.value) {
        router.put(`/admin/categories/${editingCategory.value.id}`, payload, {
            onSuccess: () => {
                showModal.value = false;
            },
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } else {
        router.post('/admin/categories', payload, {
            onSuccess: () => {
                showModal.value = false;
            },
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    }
};

const deleteCategory = (id: number, name: string) => {
    if (confirm(`Are you sure you want to delete category "${name}"? Child categories will become top-level.`)) {
        router.delete(`/admin/categories/${id}`);
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Categories Hierarchy — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Catalog Categories</h1>
                    <p class="text-xs text-[#86868B] mt-1">Configure bilingual taxonomic hierarchies, display sequence, and catalog routing.</p>
                </div>
                <Button variant="primary" size="sm" @click="openCreate" class="flex items-center gap-1.5">
                    <Plus class="w-3.5 h-3.5" />
                    <span>Create Category</span>
                </Button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Category Name</th>
                                <th class="py-3.5 px-6">Parent Level</th>
                                <th class="py-3.5 px-6">Order</th>
                                <th class="py-3.5 px-6">Assigned Products</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="categories.length === 0">
                                <td colspan="6" class="py-12 text-center text-[#86868B]">
                                    No categories created yet.
                                </td>
                            </tr>
                            <tr
                                v-for="cat in categories"
                                :key="cat.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-medium">
                                    <div class="flex items-center gap-2">
                                        <FolderTree class="w-4 h-4 text-[#86868B] shrink-0" />
                                        <div>
                                            <span class="font-bold text-[#111111]">{{ cat.name_en }}</span>
                                            <span class="text-[11px] text-[#86868B] ml-2">({{ cat.name_km }})</span>
                                            <p class="text-[10px] text-[#86868B] font-mono mt-0.5">/category/{{ cat.slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-[#1D1D1F]">
                                    {{ cat.parent_name || 'Top-level Root' }}
                                </td>
                                <td class="py-4 px-6 font-mono text-[#86868B]">
                                    #{{ cat.display_order }}
                                </td>
                                <td class="py-4 px-6 font-bold text-[#111111]">
                                    {{ cat.products_count }} products
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="cat.is_active ? 'success' : 'neutral'">
                                        {{ cat.is_active ? 'Active' : 'Disabled' }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEdit(cat)"
                                            class="p-1.5 rounded-lg text-[#86868B] hover:text-[#111111] hover:bg-[#F5F5F7] transition-colors"
                                            title="Edit Category"
                                        >
                                            <Edit3 class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            @click="deleteCategory(cat.id, cat.name_en)"
                                            class="p-1.5 rounded-lg text-[#86868B] hover:text-[#DC2626] hover:bg-[#F5F5F7] transition-colors"
                                            title="Delete Category"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog
            :open="showModal"
            @close="showModal = false"
            :title="editingCategory ? 'Edit Category' : 'Create Catalog Category'"
        >
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="form.name_en"
                        label="Name (English)"
                        placeholder="e.g. Smart Living"
                        :error="errors.name_en"
                        required
                    />
                    <Input
                        v-model="form.name_km"
                        label="Name (Khmer / ខ្មែរ)"
                        placeholder="e.g. ជីវិតសម័យថ្មី"
                        :error="errors.name_km"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        v-model="form.parent_id"
                        label="Parent Category"
                        :options="parentOptions"
                        :error="errors.parent_id"
                    />
                    <Input
                        v-model="form.display_order"
                        type="number"
                        label="Display Sequence"
                        placeholder="1"
                        :error="errors.display_order"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Textarea
                        v-model="form.description_en"
                        label="Description (English)"
                        rows="3"
                        placeholder="Category highlights..."
                        :error="errors.description_en"
                    />
                    <Textarea
                        v-model="form.description_km"
                        label="Description (Khmer)"
                        rows="3"
                        placeholder="ការពិពណ៌នា..."
                        :error="errors.description_km"
                    />
                </div>

                <label class="flex items-center gap-2 cursor-pointer pt-2">
                    <input
                        type="checkbox"
                        v-model="form.is_active"
                        class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                    />
                    <span class="text-xs text-[#1D1D1F]">Active on storefront navigation</span>
                </label>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" :loading="isSubmitting">
                        {{ editingCategory ? 'Update' : 'Create' }}
                    </Button>
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
