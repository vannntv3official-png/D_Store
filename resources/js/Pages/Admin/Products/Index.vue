<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import CustomDropdown from '@/Components/CustomDropdown.vue';
import Badge from '@/Components/Badge.vue';
import { Plus, Download, Search, Edit3, Trash2, ExternalLink } from 'lucide-vue-next';

interface ProductItem {
    id: number;
    name_en: string;
    name_km: string;
    slug: string;
    brand?: string | null;
    category?: string | null;
    base_price: string;
    stock: number;
    status: string;
    is_featured: boolean;
    created_at: string;
}

interface CategoryOption {
    id: number;
    name_en: string;
}

interface BrandOption {
    id: number;
    name: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    products: {
        data: ProductItem[];
        links: PaginationLink[];
        total: number;
    };
    categories: CategoryOption[];
    brands: BrandOption[];
    filters: {
        q?: string;
        category_id?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.q || '');
const selectedCategory = ref(props.filters.category_id || '');

const categoryOptions = computed(() => [
    { value: '', label: 'All Categories' },
    ...(props.categories || []).map((c) => ({ value: String(c.id), label: c.name_en })),
]);

const applyFilters = () => {
    router.get(
        '/admin/products',
        {
            q: search.value || undefined,
            category_id: selectedCategory.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const deleteProduct = (id: number, name: string) => {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        router.delete(`/admin/products/${id}`);
    }
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'success';
        case 'draft':
            return 'warning';
        case 'archived':
            return 'danger';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Products Management — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Products Catalog</h1>
                    <p class="text-xs text-[#86868B] mt-1">Manage luxury physical goods, variants, inventory, and localization.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin/products/export/csv" download>
                        <Button variant="secondary" size="sm" class="flex items-center gap-1.5">
                            <Download class="w-3.5 h-3.5" />
                            <span>Export CSV</span>
                        </Button>
                    </a>
                    <Link href="/admin/products/create">
                        <Button variant="primary" size="sm" class="flex items-center gap-1.5">
                            <Plus class="w-3.5 h-3.5" />
                            <span>Create Product</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters & Search Bar -->
            <div class="bg-white p-4 rounded-2xl border border-[#E5E5EA] shadow-sm flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <Search class="w-4 h-4 text-[#86868B] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search product name or SKU..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-[#F5F5F7] border border-transparent rounded-xl focus:bg-white focus:border-[#111111] outline-none transition-all"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <CustomDropdown
                        v-model="selectedCategory"
                        :options="categoryOptions"
                        @change="applyFilters"
                        align="left"
                        min-width="w-56"
                    />
                </div>

                <Button variant="secondary" size="sm" @click="applyFilters" class="w-full sm:w-auto">
                    Apply Filter
                </Button>
            </div>

            <!-- Products Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto d-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[760px]">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Product</th>
                                <th class="py-3.5 px-6">Category</th>
                                <th class="py-3.5 px-6">Brand</th>
                                <th class="py-3.5 px-6">Base Price</th>
                                <th class="py-3.5 px-6">Stock</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-[#86868B]">
                                    No products found matching criteria.
                                </td>
                            </tr>
                            <tr
                                v-for="item in products.data"
                                :key="item.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-medium">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-[#111111]">{{ item.name_en }}</span>
                                                <span v-if="item.is_featured" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800">
                                                    FEATURED
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-[#86868B] mt-0.5">{{ item.name_km }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-[#1D1D1F]">
                                    {{ item.category || '—' }}
                                </td>
                                <td class="py-4 px-6 text-[#86868B] font-medium">
                                    {{ item.brand || '—' }}
                                </td>
                                <td class="py-4 px-6 font-bold text-[#111111]">
                                    ${{ item.base_price }}
                                </td>
                                <td class="py-4 px-6">
                                    <span
                                        :class="[
                                            'font-bold',
                                            item.stock <= 5 ? 'text-[#DC2626]' : 'text-[#111111]'
                                        ]"
                                    >
                                        {{ item.stock }} units
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="getStatusBadgeVariant(item.status)">
                                        {{ item.status }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/product/${item.slug}`" target="_blank" title="View in Store">
                                            <Button variant="ghost" size="sm" class="p-1.5">
                                                <ExternalLink class="w-3.5 h-3.5" />
                                            </Button>
                                        </Link>
                                        <Link :href="`/admin/products/${item.id}/edit`" title="Edit Product">
                                            <Button variant="secondary" size="sm" class="p-1.5">
                                                <Edit3 class="w-3.5 h-3.5" />
                                            </Button>
                                        </Link>
                                        <button
                                            @click="deleteProduct(item.id, item.name_en)"
                                            class="p-1.5 rounded-lg text-[#86868B] hover:text-[#DC2626] hover:bg-[#F5F5F7] transition-colors"
                                            title="Delete Product"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="products.links.length > 3" class="p-4 border-t border-[#F5F5F7] flex items-center justify-between">
                    <p class="text-xs text-[#86868B]">
                        Showing {{ products.data.length }} of {{ products.total }} products
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in products.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded-lg text-xs font-semibold transition-colors',
                                link.active ? 'bg-[#111111] text-white' : 'text-[#86868B] hover:bg-[#F5F5F7]',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
