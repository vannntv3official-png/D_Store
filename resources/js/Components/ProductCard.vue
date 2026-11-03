<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { useTrans } from '@/Composables/useTrans';
import { ShoppingBag, Star } from 'lucide-vue-next';

interface ProductCardProps {
    id: number;
    name_en: string;
    name_km: string;
    slug: string;
    brand?: string | null;
    base_price: string;
    base_price_khr: number;
    compare_at_price?: string | null;
    primary_image: string;
    rating_cache: number;
    review_count: number;
    in_stock: boolean;
}

const props = defineProps<{
    product: ProductCardProps;
}>();

const { t, currentLocale, currentCurrency } = useTrans();

const localizedTitle = () => {
    return currentLocale.value === 'km' && props.product.name_km
        ? props.product.name_km
        : props.product.name_en;
};

const addToCart = (e: Event) => {
    e.preventDefault();
    e.stopPropagation();

    // Visit product detail for selecting variant or adding directly
    router.visit(`/products/${props.product.slug}`);
};
</script>

<template>
    <div class="group relative flex flex-col bg-white rounded-2xl border border-[#E5E5EA] overflow-hidden transition-[transform,box-shadow] duration-200 ease-out [@media(hover:hover)]:hover:-translate-y-1 [@media(hover:hover)]:hover:shadow-xl active:scale-[0.99] touch-manipulation">
        <!-- Image Container -->
        <Link :href="`/products/${product.slug}`" class="relative aspect-square w-full overflow-hidden bg-[#F5F5F7] p-3 sm:p-6 flex items-center justify-center">
            <img
                :src="product.primary_image"
                :alt="localizedTitle()"
                loading="lazy"
                class="h-full w-full object-contain object-center transition-transform duration-500 group-hover:scale-105"
            />

            <!-- In stock / Out of stock Pill -->
            <div class="absolute top-2 left-2 sm:top-3 sm:left-3">
                <span
                    v-if="!product.in_stock"
                    class="px-2 py-0.5 sm:px-2.5 sm:py-1 text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider bg-[#FEE2E2] text-[#991B1B] rounded-full"
                >
                    {{ t('product.out_of_stock') }}
                </span>
                <span
                    v-else-if="product.compare_at_price"
                    class="px-2 py-0.5 sm:px-2.5 sm:py-1 text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider bg-[#111111] text-white rounded-full shadow-sm"
                >
                    SALE
                </span>
            </div>
        </Link>

        <!-- Product Information -->
        <div class="flex flex-1 flex-col p-3.5 sm:p-5">
            <div class="text-[10px] sm:text-[11px] font-medium uppercase tracking-wider text-[#86868B] mb-0.5 sm:mb-1 truncate">
                {{ product.brand || 'D STORE' }}
            </div>

            <h3 class="text-xs sm:text-sm font-semibold text-[#1D1D1F] line-clamp-2 mb-1.5 sm:mb-2 group-hover:text-[#0071E3] transition-colors leading-snug">
                <Link :href="`/products/${product.slug}`">
                    {{ localizedTitle() }}
                </Link>
            </h3>

            <!-- Rating Stars -->
            <div class="flex items-center gap-1 mb-2 sm:mb-3">
                <div class="flex text-amber-400">
                    <Star class="w-3 h-3 sm:w-3.5 sm:h-3.5 fill-current" />
                </div>
                <span class="text-[11px] sm:text-xs text-[#86868B] font-medium">
                    {{ product.rating_cache.toFixed(1) }} <span class="hidden sm:inline">({{ product.review_count }})</span>
                </span>
            </div>

            <!-- Price & Action Footer -->
            <div class="mt-auto pt-2.5 sm:pt-3 border-t border-[#F5F5F7] flex items-center justify-between gap-1">
                <div class="min-w-0">
                    <div class="flex items-baseline gap-1.5 sm:gap-2">
                        <span class="text-sm sm:text-base font-bold text-[#111111] truncate">
                            ${{ product.base_price }}
                        </span>
                        <span
                            v-if="product.compare_at_price"
                            class="text-[10px] sm:text-xs text-[#86868B] line-through truncate hidden xs:inline"
                        >
                            ${{ product.compare_at_price }}
                        </span>
                    </div>
                    <div class="text-[10px] text-[#86868B] font-medium truncate">
                        ៛{{ product.base_price_khr.toLocaleString() }}
                    </div>
                </div>

                <button
                    type="button"
                    @click="addToCart"
                    class="p-2 sm:p-2.5 rounded-full bg-[#F5F5F7] text-[#1D1D1F] hover:bg-[#111111] hover:text-white active:scale-95 transition-[transform,background-color,color] duration-150 ease-out focus:outline-none cursor-pointer touch-manipulation select-none shrink-0"
                    :aria-label="t('product.add_to_cart')"
                >
                    <ShoppingBag class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </button>
            </div>
        </div>
    </div>
</template>
