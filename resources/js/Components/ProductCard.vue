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
    <div class="group relative flex flex-col bg-white rounded-2xl border border-[#E5E5EA] overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
        <!-- Image Container -->
        <Link :href="`/products/${product.slug}`" class="relative aspect-square w-full overflow-hidden bg-[#F5F5F7] p-6 flex items-center justify-center">
            <img
                :src="product.primary_image"
                :alt="localizedTitle()"
                loading="lazy"
                class="h-full w-full object-contain object-center transition-transform duration-500 group-hover:scale-105"
            />

            <!-- In stock / Out of stock Pill -->
            <div class="absolute top-3 left-3">
                <span
                    v-if="!product.in_stock"
                    class="px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider bg-[#FEE2E2] text-[#991B1B] rounded-full"
                >
                    {{ t('product.out_of_stock') }}
                </span>
                <span
                    v-else-if="product.compare_at_price"
                    class="px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider bg-[#111111] text-white rounded-full shadow-sm"
                >
                    SALE
                </span>
            </div>
        </Link>

        <!-- Product Information -->
        <div class="flex flex-1 flex-col p-5">
            <div class="text-[11px] font-medium uppercase tracking-wider text-[#86868B] mb-1">
                {{ product.brand || 'D STORE' }}
            </div>

            <h3 class="text-sm font-semibold text-[#1D1D1F] line-clamp-2 mb-2 group-hover:text-[#0071E3] transition-colors leading-snug">
                <Link :href="`/products/${product.slug}`">
                    {{ localizedTitle() }}
                </Link>
            </h3>

            <!-- Rating Stars -->
            <div class="flex items-center gap-1 mb-3">
                <div class="flex text-amber-400">
                    <Star class="w-3.5 h-3.5 fill-current" />
                </div>
                <span class="text-xs text-[#86868B] font-medium">
                    {{ product.rating_cache.toFixed(1) }} ({{ product.review_count }})
                </span>
            </div>

            <!-- Price & Action Footer -->
            <div class="mt-auto pt-3 border-t border-[#F5F5F7] flex items-center justify-between">
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-base font-bold text-[#111111]">
                            ${{ product.base_price }}
                        </span>
                        <span
                            v-if="product.compare_at_price"
                            class="text-xs text-[#86868B] line-through"
                        >
                            ${{ product.compare_at_price }}
                        </span>
                    </div>
                    <div class="text-[11px] text-[#86868B] font-medium">
                        ៛{{ product.base_price_khr.toLocaleString() }}
                    </div>
                </div>

                <button
                    type="button"
                    @click="addToCart"
                    class="p-2.5 rounded-full bg-[#F5F5F7] text-[#1D1D1F] hover:bg-[#111111] hover:text-white active:scale-95 transition-all focus:outline-none cursor-pointer"
                    :aria-label="t('product.add_to_cart')"
                >
                    <ShoppingBag class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>
