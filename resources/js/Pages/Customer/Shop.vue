<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Button from '@/Components/Button.vue';
import { useTrans } from '@/Composables/useTrans';
import { Filter, SlidersHorizontal, RotateCcw, ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface Props {
    products: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    categories: any[];
    brands: any[];
    filters: {
        q?: string;
        category?: string;
        brand?: string;
        min_price?: string;
        max_price?: string;
        in_stock?: boolean;
        sort?: string;
    };
}

const props = defineProps<Props>();
const { t, currentLocale } = useTrans();

const form = reactive({
    q: props.filters.q || '',
    category: props.filters.category || '',
    brand: props.filters.brand || '',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    in_stock: props.filters.in_stock || false,
    sort: props.filters.sort || 'popular',
});

const applyFilters = () => {
    router.get('/shop', {
        q: form.q || undefined,
        category: form.category || undefined,
        brand: form.brand || undefined,
        min_price: form.min_price || undefined,
        max_price: form.max_price || undefined,
        in_stock: form.in_stock ? '1' : undefined,
        sort: form.sort !== 'popular' ? form.sort : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    form.q = '';
    form.category = '';
    form.brand = '';
    form.min_price = '';
    form.max_price = '';
    form.in_stock = false;
    form.sort = 'popular';
    applyFilters();
};
</script>

<template>
    <CustomerLayout>
        <Head :title="t('shop.title')" />

        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-12">
            <!-- Header Banner -->
            <div class="mb-10 pb-8 border-b border-[#E5E5EA] flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black text-[#111111] tracking-tight">
                        {{ t('shop.title') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-2">
                        {{ t('shop.subtitle') }}
                    </p>
                </div>

                <!-- Sort & Results Count -->
                <div class="flex items-center gap-4">
                    <span class="text-xs text-[#86868B]">
                        {{ t('shop.showing_results', { from: products.from || 0, to: products.to || 0, total: products.total }) }}
                    </span>

                    <select
                        v-model="form.sort"
                        @change="applyFilters"
                        class="px-3.5 py-2 rounded-xl bg-white border border-[#E5E5EA] text-xs font-semibold text-[#1D1D1F] focus:outline-none focus:ring-2 focus:ring-[#111111] cursor-pointer"
                    >
                        <option value="popular">{{ t('shop.sort.popular') }}</option>
                        <option value="newest">{{ t('shop.sort.newest') }}</option>
                        <option value="price_asc">{{ t('shop.sort.price_asc') }}</option>
                        <option value="price_desc">{{ t('shop.sort.price_desc') }}</option>
                    </select>
                </div>
            </div>

            <!-- Two-Column Grid: Filter Sidebar + Products -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar Filters -->
                <aside class="space-y-6">
                    <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-[#F5F5F7]">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                                <SlidersHorizontal class="w-4 h-4" />
                                {{ t('shop.filter_by') }}
                            </h3>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="text-xs text-[#DC2626] hover:underline flex items-center gap-1 font-medium cursor-pointer"
                            >
                                <RotateCcw class="w-3 h-3" />
                                {{ t('shop.clear_filters') }}
                            </button>
                        </div>

                        <!-- Categories Filter -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#86868B]">
                                {{ t('nav.categories') }}
                            </label>
                            <div class="space-y-1 max-h-56 overflow-y-auto pr-1">
                                <button
                                    type="button"
                                    @click="form.category = ''; applyFilters()"
                                    class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center justify-between"
                                    :class="!form.category ? 'bg-[#111111] text-white' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span>{{ t('shop.all_categories') }}</span>
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    type="button"
                                    @click="form.category = cat.slug; applyFilters()"
                                    class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center justify-between"
                                    :class="form.category === cat.slug ? 'bg-[#111111] text-white' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span class="truncate">{{ currentLocale === 'km' && cat.name_km ? cat.name_km : cat.name_en }}</span>
                                    <span class="text-[10px] opacity-60 ml-2">({{ cat.products_count }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Brands Filter -->
                        <div class="space-y-2 pt-4 border-t border-[#F5F5F7]">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#86868B]">
                                Brands
                            </label>
                            <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                                <button
                                    v-for="brand in brands"
                                    :key="brand.id"
                                    type="button"
                                    @click="form.brand = (form.brand === brand.slug ? '' : brand.slug); applyFilters()"
                                    class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center justify-between"
                                    :class="form.brand === brand.slug ? 'bg-[#111111] text-white' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span class="truncate">{{ brand.name }}</span>
                                    <span class="text-[10px] opacity-60 ml-2">({{ brand.products_count }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="space-y-2 pt-4 border-t border-[#F5F5F7]">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#86868B]">
                                {{ t('shop.price_range') }} (USD)
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.min_price"
                                    type="number"
                                    placeholder="Min"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 bg-[#F5F5F7] rounded-xl text-xs border border-[#E5E5EA] focus:outline-none focus:border-[#111111]"
                                />
                                <span class="text-[#86868B]">-</span>
                                <input
                                    v-model="form.max_price"
                                    type="number"
                                    placeholder="Max"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 bg-[#F5F5F7] rounded-xl text-xs border border-[#E5E5EA] focus:outline-none focus:border-[#111111]"
                                />
                            </div>
                        </div>

                        <!-- In Stock Only Toggle -->
                        <div class="pt-4 border-t border-[#F5F5F7]">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input
                                    v-model="form.in_stock"
                                    type="checkbox"
                                    @change="applyFilters"
                                    class="rounded text-[#111111] focus:ring-[#111111] w-4 h-4 cursor-pointer"
                                />
                                <span class="text-xs font-medium text-[#1D1D1F]">
                                    {{ t('shop.in_stock_only') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- Products Grid -->
                <div class="lg:col-span-3">
                    <div v-if="products.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        <ProductCard
                            v-for="product in products.data"
                            :key="product.id"
                            :product="product"
                        />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-24 bg-white rounded-3xl border border-[#E5E5EA] p-8 space-y-4">
                        <p class="text-base font-bold text-[#111111]">
                            {{ t('shop.no_products') }}
                        </p>
                        <p class="text-xs text-[#86868B] max-w-sm mx-auto">
                            Try broadening your search criteria or resetting filters to browse all luxury items.
                        </p>
                        <Button variant="secondary" @click="clearFilters">
                            {{ t('shop.clear_filters') }}
                        </Button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="products.last_page > 1" class="mt-12 flex items-center justify-center gap-2">
                        <template v-for="(link, idx) in products.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors"
                                :class="link.active ? 'bg-[#111111] text-white' : 'bg-white border border-[#E5E5EA] text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3.5 py-2 text-xs text-[#86868B] opacity-50"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
