<script setup lang="ts">
import { ref, reactive, computed, watch, nextTick, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Button from '@/Components/Button.vue';
import CustomDropdown from '@/Components/CustomDropdown.vue';
import { useTrans } from '@/Composables/useTrans';
import { Filter, SlidersHorizontal, RotateCcw, ChevronLeft, ChevronRight, ChevronDown, ChevronUp, X } from 'lucide-vue-next';

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

const sortOptions = computed(() => [
    { value: 'popular', label: t('shop.sort.popular') },
    { value: 'newest', label: t('shop.sort.newest') },
    { value: 'price_asc', label: t('shop.sort.price_asc') },
    { value: 'price_desc', label: t('shop.sort.price_desc') },
]);

const showAllCategories = ref(false);
const showAllBrands = ref(false);

const displayedCategories = computed(() => {
    if (showAllCategories.value || props.categories.length <= 8) {
        return props.categories;
    }
    return props.categories.slice(0, 7);
});

const displayedBrands = computed(() => {
    if (showAllBrands.value || props.brands.length <= 8) {
        return props.brands;
    }
    return props.brands.slice(0, 7);
});

const isMobileFilterDrawerOpen = ref(false);
const filterDialog = ref<HTMLElement | null>(null);
let filterOpener: HTMLElement | null = null;
let previousOverflow = '';

watch(isMobileFilterDrawerOpen, async (isOpen) => {
    if (isOpen) {
        filterOpener = document.activeElement as HTMLElement;
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        await nextTick();
        filterDialog.value?.focus();
    } else {
        document.body.style.overflow = previousOverflow;
        filterOpener?.focus();
    }
});

onUnmounted(() => {
    if (isMobileFilterDrawerOpen.value) document.body.style.overflow = previousOverflow;
});

const handleFilterKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        isMobileFilterDrawerOpen.value = false;
    }
    if (event.key !== 'Tab') return;
    const controls = filterDialog.value?.querySelectorAll<HTMLElement>('button:not(:disabled), input:not(:disabled), a[href]');
    if (!controls?.length) return;
    const first = controls[0];
    const last = controls[controls.length - 1];
    if (event.shiftKey && (document.activeElement === first || document.activeElement === filterDialog.value)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

const activeFilterCount = computed(() => {
    let count = 0;
    if (form.category) count++;
    if (form.brand) count++;
    if (form.min_price || form.max_price) count++;
    if (form.in_stock) count++;
    return count;
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

                    <CustomDropdown
                        v-model="form.sort"
                        :options="sortOptions"
                        @change="applyFilters"
                        align="right"
                    />
                </div>
            </div>

            <!-- Two-Column Grid: Filter Sidebar + Products -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
                <!-- Sidebar Filters (Desktop Sticky - Never Scrolls Away with Page) -->
                <aside class="space-y-6 lg:sticky lg:top-24 self-start hidden lg:block">
                    <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm space-y-6 max-h-[calc(100vh-8rem)] overflow-y-auto d-scrollbar">
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
                            <div class="space-y-1 max-h-80 overflow-y-auto d-scrollbar pr-3">
                                <button
                                    type="button"
                                    @click="form.category = ''; applyFilters()"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium transition-[background-color,color] duration-150 flex items-center justify-between cursor-pointer touch-manipulation select-none active:scale-[0.98]"
                                    :class="!form.category ? 'bg-[#111111] text-white shadow-sm' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span>{{ t('shop.all_categories') }}</span>
                                </button>
                                <button
                                    v-for="cat in displayedCategories"
                                    :key="cat.id"
                                    type="button"
                                    @click="form.category = cat.slug; applyFilters()"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium transition-[background-color,color] duration-150 flex items-center justify-between cursor-pointer touch-manipulation select-none active:scale-[0.98]"
                                    :class="form.category === cat.slug ? 'bg-[#111111] text-white shadow-sm' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span class="truncate">{{ currentLocale === 'km' && cat.name_km ? cat.name_km : cat.name_en }}</span>
                                    <span class="text-[10px] opacity-60 ml-2">({{ cat.products_count }})</span>
                                </button>
                            </div>
                            <button
                                v-if="categories.length > 8"
                                type="button"
                                @click="showAllCategories = !showAllCategories"
                                class="w-full py-1.5 text-[11px] font-semibold text-[#0071E3] hover:text-[#0077ED] flex items-center justify-center gap-1 transition-colors cursor-pointer"
                            >
                                <span>{{ showAllCategories ? 'Show Less' : `+ Show ${categories.length - 7} More` }}</span>
                                <component :is="showAllCategories ? ChevronUp : ChevronDown" class="w-3 h-3" />
                            </button>
                        </div>

                        <!-- Brands Filter -->
                        <div class="space-y-2 pt-4 border-t border-[#F5F5F7]">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#86868B]">
                                Brands
                            </label>
                            <div class="space-y-1 max-h-80 overflow-y-auto d-scrollbar pr-3">
                                <button
                                    v-for="brand in displayedBrands"
                                    :key="brand.id"
                                    type="button"
                                    @click="form.brand = (form.brand === brand.slug ? '' : brand.slug); applyFilters()"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium transition-[background-color,color] duration-150 flex items-center justify-between cursor-pointer touch-manipulation select-none active:scale-[0.98]"
                                    :class="form.brand === brand.slug ? 'bg-[#111111] text-white shadow-sm' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span class="truncate">{{ brand.name }}</span>
                                    <span class="text-[10px] opacity-60 ml-2">({{ brand.products_count }})</span>
                                </button>
                            </div>
                            <button
                                v-if="brands.length > 8"
                                type="button"
                                @click="showAllBrands = !showAllBrands"
                                class="w-full py-1.5 text-[11px] font-semibold text-[#0071E3] hover:text-[#0077ED] flex items-center justify-center gap-1 transition-colors cursor-pointer"
                            >
                                <span>{{ showAllBrands ? 'Show Less' : `+ Show ${brands.length - 7} More` }}</span>
                                <component :is="showAllBrands ? ChevronUp : ChevronDown" class="w-3 h-3" />
                            </button>
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

                <!-- Products Grid & Mobile Toolbar -->
                <div class="lg:col-span-3">
                    <!-- Mobile & Tablet Filter Trigger Strip -->
                    <div class="lg:hidden flex items-center justify-between gap-3 mb-6 bg-white p-3 rounded-2xl border border-[#E5E5EA] shadow-xs">
                        <button
                            type="button"
                            @click="isMobileFilterDrawerOpen = true"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#F5F5F7] text-xs font-bold text-[#111111] hover:bg-[#EAEAEA] active:scale-95 transition-all cursor-pointer select-none"
                        >
                            <SlidersHorizontal class="w-3.5 h-3.5" />
                            <span>{{ t('shop.filter_by') }}</span>
                            <span
                                v-if="activeFilterCount > 0"
                                class="w-5 h-5 rounded-full bg-[#111111] text-white text-[10px] flex items-center justify-center font-bold"
                            >
                                {{ activeFilterCount }}
                            </span>
                        </button>

                        <span class="text-xs text-[#86868B] font-medium">
                            {{ products.total }} items
                        </span>
                    </div>

                    <div v-if="products.data.length > 0" class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-6">
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

        <!-- Mobile & Tablet Filter Drawer / Sheet -->
        <Teleport to="body">
            <div
                v-if="isMobileFilterDrawerOpen"
                @keydown="handleFilterKeydown"
                class="fixed inset-0 z-50 lg:hidden flex justify-end"
            >
                <!-- Backdrop -->
                <div
                    @click="isMobileFilterDrawerOpen = false"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                ></div>

                <!-- Slide-over Content -->
                <transition name="drawer-slide" appear>
                    <div ref="filterDialog" role="dialog" aria-modal="true" :aria-label="t('shop.filter_by')" tabindex="-1" class="relative w-full max-w-md bg-white h-full flex flex-col z-10 shadow-2xl pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)]">
                    <!-- Drawer Header -->
                    <div class="h-16 px-6 border-b border-[#E5E5EA] flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <SlidersHorizontal class="w-4 h-4 text-[#111111]" />
                            <h3 class="text-sm font-bold uppercase tracking-wider text-[#111111]">
                                {{ t('shop.filter_by') }}
                            </h3>
                            <span v-if="activeFilterCount > 0" class="px-2 py-0.5 rounded-full bg-[#111111] text-white text-[10px] font-bold">
                                {{ activeFilterCount }}
                            </span>
                        </div>

                        <div class="flex items-center gap-4">
                            <button
                                v-if="activeFilterCount > 0"
                                type="button"
                                @click="clearFilters(); isMobileFilterDrawerOpen = false"
                                class="text-xs text-[#DC2626] hover:underline font-semibold cursor-pointer"
                            >
                                {{ t('shop.clear_filters') }}
                            </button>
                            <button
                                type="button"
                                @click="isMobileFilterDrawerOpen = false"
                                class="p-2 rounded-xl text-[#86868B] hover:text-[#111111] hover:bg-[#F5F5F7] transition-colors cursor-pointer"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Drawer Scrollable Body -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6 d-scrollbar">
                        <!-- Categories Filter -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#86868B]">
                                {{ t('nav.categories') }}
                            </label>
                            <div class="space-y-1">
                                <button
                                    type="button"
                                    @click="form.category = ''; applyFilters(); isMobileFilterDrawerOpen = false"
                                    class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-medium transition-[background-color,color] duration-150 flex items-center justify-between cursor-pointer"
                                    :class="!form.category ? 'bg-[#111111] text-white shadow-sm font-semibold' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span>{{ t('shop.all_categories') }}</span>
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    type="button"
                                    @click="form.category = cat.slug; applyFilters(); isMobileFilterDrawerOpen = false"
                                    class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-medium transition-[background-color,color] duration-150 flex items-center justify-between cursor-pointer"
                                    :class="form.category === cat.slug ? 'bg-[#111111] text-white shadow-sm font-semibold' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span>{{ currentLocale === 'km' && cat.name_km ? cat.name_km : cat.name_en }}</span>
                                    <span class="text-[10px] opacity-60">({{ cat.products_count }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Brands Filter -->
                        <div class="space-y-2 pt-4 border-t border-[#F5F5F7]">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#86868B]">
                                Brands
                            </label>
                            <div class="space-y-1">
                                <button
                                    v-for="brand in brands"
                                    :key="brand.id"
                                    type="button"
                                    @click="form.brand = (form.brand === brand.slug ? '' : brand.slug); applyFilters(); isMobileFilterDrawerOpen = false"
                                    class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-medium transition-[background-color,color] duration-150 flex items-center justify-between cursor-pointer"
                                    :class="form.brand === brand.slug ? 'bg-[#111111] text-white shadow-sm font-semibold' : 'text-[#1D1D1F] hover:bg-[#F5F5F7]'"
                                >
                                    <span>{{ brand.name }}</span>
                                    <span class="text-[10px] opacity-60">({{ brand.products_count }})</span>
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
                                    class="w-full px-3 py-2.5 bg-[#F5F5F7] rounded-xl text-xs border border-[#E5E5EA] focus:outline-none focus:border-[#111111]"
                                />
                                <span class="text-[#86868B]">-</span>
                                <input
                                    v-model="form.max_price"
                                    type="number"
                                    placeholder="Max"
                                    class="w-full px-3 py-2.5 bg-[#F5F5F7] rounded-xl text-xs border border-[#E5E5EA] focus:outline-none focus:border-[#111111]"
                                />
                            </div>
                        </div>

                        <!-- In Stock Only Toggle -->
                        <div class="pt-4 border-t border-[#F5F5F7]">
                            <label class="flex items-center gap-3 cursor-pointer py-1">
                                <input
                                    v-model="form.in_stock"
                                    type="checkbox"
                                    class="rounded text-[#111111] focus:ring-[#111111] w-5 h-5 cursor-pointer"
                                />
                                <span class="text-sm font-medium text-[#1D1D1F]">
                                    {{ t('shop.in_stock_only') }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Drawer Footer Action -->
                    <div class="p-4 border-t border-[#E5E5EA] bg-white">
                        <Button
                            variant="primary"
                            size="lg"
                            class="w-full"
                            @click="applyFilters(); isMobileFilterDrawerOpen = false"
                        >
                            Show Results ({{ products.total }})
                        </Button>
                    </div>
                </div>
                </transition>
            </div>
        </Teleport>
    </CustomerLayout>
</template>
