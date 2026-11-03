<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import Textarea from '@/Components/Textarea.vue';
import { ArrowLeft, Save, Plus, Trash2, Layers } from 'lucide-vue-next';

interface CategoryOption {
    id: number;
    name_en: string;
    name_km: string;
}

interface BrandOption {
    id: number;
    name: string;
}

interface ProductVariantData {
    id?: number;
    sku: string;
    price: string | number;
    initial_stock?: number;
    stock?: number;
    barcode?: string | null;
}

interface ProductData {
    id?: number;
    name_en: string;
    name_km: string;
    slug?: string;
    description_en?: string;
    description_km?: string;
    category_id: number;
    brand_id?: number | null;
    status: string;
    is_featured: boolean;
    variants?: ProductVariantData[];
    images?: any[];
}

interface Props {
    product: ProductData | null;
    categories: CategoryOption[];
    brands: BrandOption[];
}

const props = defineProps<Props>();
const isEdit = computed(() => !!props.product?.id);

const form = reactive({
    name_en: props.product?.name_en || '',
    name_km: props.product?.name_km || '',
    category_id: props.product?.category_id || (props.categories[0]?.id || 1),
    brand_id: props.product?.brand_id || '',
    description_en: props.product?.description_en || '',
    description_km: props.product?.description_km || '',
    status: props.product?.status || 'active',
    is_featured: props.product?.is_featured || false,
    variants: props.product?.variants && props.product.variants.length > 0
        ? props.product.variants.map((v) => ({
              sku: v.sku,
              price: v.price,
              initial_stock: v.stock || 10,
              barcode: v.barcode || '',
          }))
        : [
              {
                  sku: '',
                  price: '0.00',
                  initial_stock: 10,
                  barcode: '',
              },
          ],
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const addVariantRow = () => {
    form.variants.push({
        sku: '',
        price: '0.00',
        initial_stock: 5,
        barcode: '',
    });
};

const removeVariantRow = (index: number) => {
    if (form.variants.length > 1) {
        form.variants.splice(index, 1);
    }
};

const categoryOptions = computed(() =>
    props.categories.map((c) => ({
        value: c.id,
        label: `${c.name_en} (${c.name_km || ''})`,
    }))
);

const brandOptions = computed(() => [
    { value: '', label: 'None / Generic' },
    ...props.brands.map((b) => ({
        value: b.id,
        label: b.name,
    })),
]);

const statusOptions = [
    { value: 'active', label: 'Active (Visible on storefront)' },
    { value: 'draft', label: 'Draft (Hidden from customers)' },
    { value: 'archived', label: 'Archived (Discontinued)' },
];

const submit = () => {
    isSubmitting.value = true;
    errors.value = {};

    if (isEdit.value) {
        router.put(`/admin/products/${props.product!.id}`, form, {
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } else {
        router.post('/admin/products', form, {
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="`${isEdit ? 'Edit' : 'Create'} Product — D STORE`" />

        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/admin/products" class="p-2 rounded-xl bg-white border border-[#E5E5EA] text-[#86868B] hover:text-[#111111] transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black text-[#111111] tracking-tight">
                            {{ isEdit ? 'Edit Product' : 'Create New Luxury Product' }}
                        </h1>
                        <p class="text-xs text-[#86868B] mt-0.5">
                            {{ isEdit ? `Updating ID #${product?.id}` : 'Provide bilingual names, inventory variants, and specifications.' }}
                        </p>
                    </div>
                </div>

                <Button variant="primary" @click="submit" :loading="isSubmitting" class="flex items-center gap-2">
                    <Save class="w-4 h-4" />
                    <span>{{ isEdit ? 'Update Product' : 'Publish Product' }}</span>
                </Button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Info Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                        1. Bilingual Identity & Classification
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Input
                            v-model="form.name_en"
                            label="Product Title (English)"
                            placeholder="e.g. iPhone 16 Pro Max 256GB"
                            :error="errors.name_en"
                            required
                        />
                        <Input
                            v-model="form.name_km"
                            label="Product Title (Khmer / ខ្មែរ)"
                            placeholder="e.g. ទូរស័ព្ទ iPhone 16 Pro Max"
                            :error="errors.name_km"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Select
                            v-model="form.category_id"
                            label="Primary Category"
                            :options="categoryOptions"
                            :error="errors.category_id"
                            required
                        />
                        <Select
                            v-model="form.brand_id"
                            label="Brand"
                            :options="brandOptions"
                            :error="errors.brand_id"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Textarea
                            v-model="form.description_en"
                            label="Editorial Description (English)"
                            rows="4"
                            placeholder="Craftsmanship details, features, and specs..."
                            :error="errors.description_en"
                        />
                        <Textarea
                            v-model="form.description_km"
                            label="Editorial Description (Khmer / ខ្មែរ)"
                            rows="4"
                            placeholder="ព័ត៌មានលម្អិតអំពីផលិតផល លក្ខណៈពិសេស..."
                            :error="errors.description_km"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-[#F5F5F7]">
                        <Select
                            v-model="form.status"
                            label="Visibility Status"
                            :options="statusOptions"
                            :error="errors.status"
                            required
                        />
                        <div class="flex items-center pt-6">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_featured"
                                    class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                                />
                                <span class="text-xs font-semibold text-[#111111]">Feature prominently on homepage showcase</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Variants & Inventory Card (Required for create) -->
                <div v-if="!isEdit" class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                                2. Stock Keeping Units (SKUs) & Initial Inventory
                            </h2>
                            <p class="text-xs text-[#86868B] mt-0.5">At least one variant is required with unique SKU and initial stock.</p>
                        </div>
                        <Button type="button" variant="secondary" size="sm" @click="addVariantRow" class="flex items-center gap-1.5">
                            <Plus class="w-3.5 h-3.5" />
                            <span>Add Variant</span>
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(v, idx) in form.variants"
                            :key="idx"
                            class="p-4 rounded-2xl bg-[#FBFBFC] border border-[#E5E5EA] flex flex-col sm:flex-row items-start sm:items-center gap-4"
                        >
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3 w-full">
                                <Input
                                    v-model="v.sku"
                                    label="SKU"
                                    placeholder="e.g. IP16PM-256-TI"
                                    :error="errors[`variants.${idx}.sku`]"
                                    required
                                />
                                <Input
                                    v-model="v.price"
                                    type="number"
                                    step="0.01"
                                    label="Price (USD)"
                                    placeholder="0.00"
                                    :error="errors[`variants.${idx}.price`]"
                                    required
                                />
                                <Input
                                    v-model="v.initial_stock"
                                    type="number"
                                    label="Initial Stock"
                                    placeholder="10"
                                    :error="errors[`variants.${idx}.initial_stock`]"
                                    required
                                />
                                <Input
                                    v-model="v.barcode"
                                    label="Barcode (Optional)"
                                    placeholder="e.g. 194253000000"
                                />
                            </div>

                            <button
                                v-if="form.variants.length > 1"
                                type="button"
                                @click="removeVariantRow(idx)"
                                class="p-2 text-[#86868B] hover:text-[#DC2626] rounded-lg hover:bg-white transition-colors sm:self-center"
                                title="Remove Variant"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4">
                    <Link href="/admin/products">
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :loading="isSubmitting">
                        {{ isEdit ? 'Save Changes' : 'Create Product' }}
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
