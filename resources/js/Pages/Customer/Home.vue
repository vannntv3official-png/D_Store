<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { useTrans } from '@/Composables/useTrans';
import { Shield, Sparkles, Truck, Headphones, ArrowRight, Star } from 'lucide-vue-next';

interface Props {
    banners: any[];
    categories: any[];
    trendingProducts: any[];
    newArrivals: any[];
    reviews: any[];
}

const props = defineProps<Props>();
const { t, currentLocale } = useTrans();
</script>

<template>
    <CustomerLayout>
        <Head :title="t('app.name') + ' — ' + t('app.slogan')" />

        <!-- Hero Section -->
        <section class="relative bg-[#111111] text-white overflow-hidden py-24 sm:py-32">
            <!-- Background Radial Glow -->
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(0,113,227,0.18),transparent_50%)]"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-8 text-center space-y-6">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-white/80 text-xs font-semibold uppercase tracking-widest border border-white/10">
                    <Sparkles class="w-3.5 h-3.5 text-[#0071E3]" />
                    The New Standard of Luxury in Cambodia
                </span>

                <h1 class="text-4xl sm:text-6xl md:text-7xl font-black tracking-tight text-white max-w-4xl mx-auto leading-tight">
                    {{ currentLocale === 'km' ? 'ស្វែងរកភាពប្រណីត និងគុណភាពខ្ពស់' : 'Discover More. Shop Better.' }}
                </h1>

                <p class="text-base sm:text-xl text-[#86868B] max-w-2xl mx-auto font-normal leading-relaxed">
                    {{ currentLocale === 'km' ? 'ផលិតផលសុទ្ធ ១០០% ធានាផ្លូវការ ដឹកជញ្ជូនរហ័សទូទាំងរាជធានីភ្នំពេញ និងគ្រប់ខេត្ត។' : 'Curated flagship technology, fine fashion, and automotive essentials delivered with distinction.' }}
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <Link
                        href="/shop"
                        class="px-8 py-4 rounded-2xl bg-white text-[#111111] font-bold text-sm hover:bg-[#F5F5F7] transition-all shadow-lg hover:shadow-2xl hover:scale-105 active:scale-95"
                    >
                        {{ t('hero.shop_now') }}
                    </Link>
                    <Link
                        href="/pages/about-us"
                        class="px-8 py-4 rounded-2xl bg-white/10 text-white font-semibold text-sm hover:bg-white/20 transition-all border border-white/15"
                    >
                        {{ t('hero.learn_more') }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Brand Pillars Bar -->
        <section class="border-b border-[#E5E5EA] bg-white py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111]">
                        <Shield class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111]">100% Authentic</h4>
                        <p class="text-[11px] text-[#86868B]">Official Warranty Guarantee</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111]">
                        <Truck class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111]">Express Delivery</h4>
                        <p class="text-[11px] text-[#86868B]">Phnom Penh & 24 Provinces</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111]">
                        <Sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111]">Bakong KHQR</h4>
                        <p class="text-[11px] text-[#86868B]">Seamless Mobile Payments</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111]">
                        <Headphones class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111]">Client Concierge</h4>
                        <p class="text-[11px] text-[#86868B]">Dedicated Support 7 Days</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Curated Categories Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-8 py-20">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111111] tracking-tight">
                        {{ t('home.featured_categories') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-1">
                        {{ t('home.featured_subtitle') }}
                    </p>
                </div>
                <Link href="/shop" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-[#0071E3] hover:underline">
                    <span>View All</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
                <Link
                    v-for="cat in categories"
                    :key="cat.id"
                    :href="`/shop?category=${cat.slug}`"
                    class="group relative bg-white rounded-2xl border border-[#E5E5EA] p-6 text-center overflow-hidden transition-all duration-300 hover:shadow-xl hover:border-[#111111]"
                >
                    <div class="aspect-square w-16 h-16 mx-auto mb-4 rounded-full bg-[#F5F5F7] flex items-center justify-center group-hover:scale-110 transition-transform">
                        <Sparkles class="w-6 h-6 text-[#111111]" />
                    </div>
                    <h3 class="text-sm font-bold text-[#1D1D1F] group-hover:text-[#0071E3] transition-colors">
                        {{ currentLocale === 'km' && cat.name_km ? cat.name_km : cat.name_en }}
                    </h3>
                </Link>
            </div>
        </section>

        <!-- Trending Products Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-8 py-12">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111111] tracking-tight">
                        {{ t('home.trending_products') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-1">
                        {{ t('home.trending_subtitle') }}
                    </p>
                </div>
                <Link href="/shop" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-[#0071E3] hover:underline">
                    <span>Explore Catalog</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-6">
                <ProductCard
                    v-for="product in trendingProducts"
                    :key="product.id"
                    :product="product"
                />
            </div>
        </section>

        <!-- New Arrivals Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-8 py-16">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111111] tracking-tight">
                        {{ t('home.new_arrivals') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-1">
                        Latest editions added to our Phnom Penh collection.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-6">
                <ProductCard
                    v-for="product in newArrivals"
                    :key="product.id"
                    :product="product"
                />
            </div>
        </section>

        <!-- Customer Testimonials Section -->
        <section class="bg-white border-y border-[#E5E5EA] py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-8">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111111] tracking-tight">
                        {{ t('home.reviews_title') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-2">
                        Authentic experiences from verified purchasers across Cambodia.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        v-for="rev in reviews"
                        :key="rev.id"
                        class="bg-[#F5F5F7] p-8 rounded-3xl border border-[#E5E5EA] space-y-4"
                    >
                        <div class="flex text-amber-400">
                            <Star v-for="i in rev.rating" :key="i" class="w-4 h-4 fill-current" />
                        </div>
                        <h4 class="text-sm font-bold text-[#111111]">
                            {{ rev.title }}
                        </h4>
                        <p class="text-xs text-[#1D1D1F] leading-relaxed">
                            "{{ rev.comment }}"
                        </p>
                        <div class="pt-2 border-t border-[#E5E5EA] flex items-center justify-between text-[11px] text-[#86868B]">
                            <span class="font-semibold text-[#111111]">{{ rev.user_name }}</span>
                            <span>{{ rev.created_at }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>
