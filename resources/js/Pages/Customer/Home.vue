<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { useTrans } from '@/Composables/useTrans';
import { 
    Shield, Sparkles, Truck, Headphones, ArrowRight, Star, 
    ChevronLeft, ChevronRight, Zap, CheckCircle2
} from 'lucide-vue-next';

interface Props {
    banners: any[];
    categories: any[];
    trendingProducts: any[];
    newArrivals: any[];
    reviews: any[];
}

const props = defineProps<Props>();
const { t, currentLocale } = useTrans();

// Showcase Carousel State & Logic
const currentSlideIndex = ref(0);
const isPaused = ref(false);
let autoplayInterval: any = null;

// Curated luxury fallback slides when no dynamic banners are active
const defaultBanners = [
    {
        id: 'default-1',
        title_en: 'The Next Era of Personal Technology',
        title_km: 'យុគសម័យថ្មីនៃបច្ចេកវិទ្យាផ្ទាល់ខ្លួន',
        subtitle_en: 'Precision-crafted titanium flagships, ultra-fast neural silicon, and revolutionary mobile intelligence.',
        subtitle_km: 'ឧបករណ៍ទំនើបចុងក្រោយបង្អស់ ដែកទីតានីញ៉ូមប្រណីត និងបច្ចេកវិទ្យាឆ្លាតវៃឈានមុខគេបង្អស់។',
        badge_en: 'Official Flagship Release',
        badge_km: 'ការចេញលក់ផ្លូវការថ្មី',
        button_text_en: 'Explore Flagships',
        button_text_km: 'ស្វែងយល់ពីផលិតផល',
        link_url: '/shop?category=smartphones',
        image_url: 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?q=80&w=1600&auto=format&fit=crop',
        gradient: 'from-[#0A0A0E] via-[#0E131E] to-[#122033]',
        glow_color: 'rgba(0,113,227,0.25)',
    },
    {
        id: 'default-2',
        title_en: 'Monochrome Minimalism & Timeless Tailoring',
        title_km: 'ម៉ូដសម្លៀកបំពាក់ទាន់សម័យ និងប្រណិតភាព',
        subtitle_en: 'Curated wardrobe essentials crafted in pure cotton, silk, and structured fine wool.',
        subtitle_km: 'ការច្នៃម៉ូដដ៏ប្រណិតពីសាច់ក្រណាត់កប្បាស សូត្រធម្មជាតិ និងរោមចៀមគុណភាពខ្ពស់បំផុត។',
        badge_en: 'Private Haute Couture',
        badge_km: 'បណ្តុំម៉ូដប្រណិតផ្តាច់មុខ',
        button_text_en: 'Shop Collection',
        button_text_km: 'ទិញការប្រមូលផ្ដុំ',
        link_url: '/shop?category=fashion-clothing',
        image_url: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1600&auto=format&fit=crop',
        gradient: 'from-[#0E0C10] via-[#1A1422] to-[#2B1D38]',
        glow_color: 'rgba(168,85,247,0.22)',
    },
    {
        id: 'default-3',
        title_en: 'Spatial Sound. Uncompromising Clarity.',
        title_km: 'គុណភាពសំឡេងកម្រិតកំពូល និងភាពរស់រវើក',
        subtitle_en: 'Acoustic precision and wireless spatial immersion tuned for the most discerning listeners in Cambodia.',
        subtitle_km: 'បទពិសោធន៍សំឡេងច្បាស់ល្អឥតខ្ចោះ ជាមួយប្រព័ន្ធកាត់បន្ថយសំឡេងរំខានកម្រិតស្ទូឌីយោ។',
        badge_en: 'Audiophile Distinction',
        badge_km: 'កម្រិតស្តង់ដារសំឡេងកំពូល',
        button_text_en: 'Discover Audio',
        button_text_km: 'ស្វែងយល់ប្រព័ន្ធសំឡេង',
        link_url: '/shop?category=electronics',
        image_url: 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1600&auto=format&fit=crop',
        gradient: 'from-[#070D0E] via-[#0D1B1E] to-[#142E35]',
        glow_color: 'rgba(16,185,129,0.22)',
    },
];

const activeSlides = computed(() => {
    if (props.banners && props.banners.length > 0) {
        return props.banners;
    }
    return defaultBanners;
});

const currentSlide = computed(() => {
    return activeSlides.value[currentSlideIndex.value] || activeSlides.value[0];
});

const nextSlide = () => {
    currentSlideIndex.value = (currentSlideIndex.value + 1) % activeSlides.value.length;
};

const prevSlide = () => {
    currentSlideIndex.value = (currentSlideIndex.value - 1 + activeSlides.value.length) % activeSlides.value.length;
};

const goToSlide = (idx: number) => {
    currentSlideIndex.value = idx;
};

// Touch gestures support
const touchStartX = ref(0);
const touchEndX = ref(0);

const handleTouchStart = (e: TouchEvent) => {
    touchStartX.value = e.touches[0].clientX;
};

const handleTouchEnd = (e: TouchEvent) => {
    touchEndX.value = e.changedTouches[0].clientX;
    const diff = touchStartX.value - touchEndX.value;
    if (Math.abs(diff) > 45) {
        if (diff > 0) nextSlide();
        else prevSlide();
    }
};

onMounted(() => {
    autoplayInterval = setInterval(() => {
        if (!isPaused.value && activeSlides.value.length > 1) {
            nextSlide();
        }
    }, 6000);
});

onUnmounted(() => {
    if (autoplayInterval) {
        clearInterval(autoplayInterval);
    }
});
</script>

<template>
    <CustomerLayout>
        <Head :title="t('app.name') + ' — ' + t('app.slogan')" />

        <!-- Luxury Hero Showcase Banner Slider -->
        <section 
            class="relative max-w-7xl mx-auto px-0 sm:px-8 sm:py-6"
            @mouseenter="isPaused = true"
            @mouseleave="isPaused = false"
            @touchstart="handleTouchStart"
            @touchend="handleTouchEnd"
        >
            <div class="relative overflow-hidden bg-[#0A0A0C] text-white rounded-none sm:rounded-3xl min-h-[460px] sm:min-h-[520px] md:min-h-[560px] lg:min-h-[600px] flex items-center shadow-2xl border border-black/5">
                <!-- Background Image Layer -->
                <transition-group name="fade" tag="div" class="absolute inset-0 w-full h-full">
                    <div
                        v-for="(slide, idx) in activeSlides"
                        :key="slide.id || idx"
                        v-show="currentSlideIndex === idx"
                        class="absolute inset-0 w-full h-full"
                    >
                        <!-- Fallback Background Gradient -->
                        <div 
                            class="absolute inset-0 bg-gradient-to-tr"
                            :class="slide.gradient || 'from-[#0B0B0E] via-[#14141A] to-[#1E1E26]'"
                        ></div>

                        <!-- Atmospheric Glow -->
                        <div 
                            class="absolute inset-0 opacity-40 mix-blend-screen"
                            :style="{ background: `radial-gradient(circle at 75% 30%, ${slide.glow_color || 'rgba(0,113,227,0.3)'}, transparent 60%)` }"
                        ></div>

                        <!-- Banner Image with Safe Error Fallback -->
                        <img
                            v-if="slide.image_url"
                            :src="slide.image_url"
                            :alt="currentLocale === 'km' && slide.title_km ? slide.title_km : slide.title_en"
                            class="absolute inset-0 w-full h-full object-cover object-center scale-100 transition-transform duration-1000 ease-out"
                            @error="(e: any) => e.target.style.display = 'none'"
                        />

                        <!-- Gradient Scrim for Impeccable Typography Contrast -->
                        <div class="absolute inset-0 bg-gradient-to-t sm:bg-gradient-to-r from-black/95 via-black/75 sm:via-black/55 to-black/25"></div>
                    </div>
                </transition-group>

                <!-- Foreground Content Container -->
                <div class="relative z-10 max-w-7xl w-full mx-auto px-6 sm:px-12 md:px-16 py-16 sm:py-24">
                    <transition name="hero-text" mode="out-in">
                        <div :key="currentSlideIndex" class="max-w-2xl space-y-5 sm:space-y-6">
                            <!-- Pill Badge -->
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-white/90 text-[11px] font-bold uppercase tracking-widest border border-white/15 shadow-sm d-shimmer">
                                <Sparkles class="w-3.5 h-3.5 text-[#D4AF37]" />
                                <span>
                                    {{ currentLocale === 'km' 
                                        ? (currentSlide.badge_km || 'ការជ្រើសរើសពិសេស') 
                                        : (currentSlide.badge_en || 'Showcase Release') 
                                    }}
                                </span>
                            </div>

                            <!-- Main Banner Title -->
                            <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight text-white leading-[1.15] drop-shadow-sm">
                                {{ currentLocale === 'km' && currentSlide.title_km ? currentSlide.title_km : currentSlide.title_en }}
                            </h1>

                            <!-- Subtitle Description -->
                            <p class="text-sm sm:text-base md:text-lg text-white/80 font-normal leading-relaxed line-clamp-3 max-w-xl">
                                {{ currentLocale === 'km' && currentSlide.subtitle_km ? currentSlide.subtitle_km : (currentSlide.subtitle_en || 'Curated luxury essentials delivered with distinction in Cambodia.') }}
                            </p>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap items-center gap-3.5 pt-2">
                                <Link
                                    :href="currentSlide.link_url || '/shop'"
                                    class="inline-flex items-center gap-2 px-7 py-3.5 sm:px-8 sm:py-4 rounded-2xl bg-white text-[#111111] font-bold text-xs sm:text-sm hover:bg-[#F5F5F7] transition-all shadow-xl hover:shadow-2xl hover:scale-105 active:scale-95 cursor-pointer touch-manipulation group d-shimmer"
                                >
                                    <span>
                                        {{ currentLocale === 'km'
                                            ? (currentSlide.button_text_km || t('hero.shop_now'))
                                            : (currentSlide.button_text_en || t('hero.shop_now'))
                                        }}
                                    </span>
                                    <ArrowRight class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" />
                                </Link>

                                <Link
                                    href="/shop"
                                    class="inline-flex items-center gap-2 px-6 py-3.5 sm:px-7 sm:py-4 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs sm:text-sm backdrop-blur-md transition-all border border-white/15 cursor-pointer active:scale-95"
                                >
                                    <span>{{ t('nav.shop') }}</span>
                                </Link>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Floating Previous / Next Slide Controls (Desktop / Tablet) -->
                <div v-if="activeSlides.length > 1" class="hidden sm:flex absolute inset-y-0 right-6 sm:right-10 z-20 flex-col items-center justify-center gap-3">
                    <button
                        type="button"
                        @click="prevSlide"
                        class="w-11 h-11 rounded-full bg-white/10 hover:bg-white text-white hover:text-[#111111] border border-white/20 backdrop-blur-md flex items-center justify-center transition-all cursor-pointer shadow-lg active:scale-95"
                        aria-label="Previous slide"
                    >
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    <button
                        type="button"
                        @click="nextSlide"
                        class="w-11 h-11 rounded-full bg-white/10 hover:bg-white text-white hover:text-[#111111] border border-white/20 backdrop-blur-md flex items-center justify-center transition-all cursor-pointer shadow-lg active:scale-95"
                        aria-label="Next slide"
                    >
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>

                <!-- Bottom Slide Pagination Dots & Index Indicator -->
                <div v-if="activeSlides.length > 1" class="absolute bottom-6 left-6 sm:left-12 z-20 flex items-center gap-3">
                    <div class="flex items-center gap-1.5 p-1 rounded-full bg-black/40 backdrop-blur-md border border-white/10">
                        <button
                            v-for="(_, idx) in activeSlides"
                            :key="idx"
                            type="button"
                            @click="goToSlide(idx)"
                            class="h-2 rounded-full transition-all duration-300 cursor-pointer"
                            :class="currentSlideIndex === idx ? 'w-8 bg-white' : 'w-2 bg-white/40 hover:bg-white/70'"
                            :aria-label="`Go to slide ${idx + 1}`"
                        ></button>
                    </div>

                    <!-- Slide Counter -->
                    <span class="text-[11px] font-mono font-bold text-white/70 bg-black/40 backdrop-blur-md px-2.5 py-0.5 rounded-full border border-white/10">
                        {{ String(currentSlideIndex + 1).padStart(2, '0') }} / {{ String(activeSlides.length).padStart(2, '0') }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Brand Pillars Bar -->
        <section class="border-y border-[#E5E5EA] bg-white py-6 sm:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                <div class="group flex items-center gap-3.5 p-3 rounded-2xl transition-all duration-200 hover:bg-[#F5F5F7]/80 cursor-default">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111] shrink-0 transition-all duration-300 ease-out group-hover:scale-110 group-hover:-rotate-3 group-hover:bg-[#111111] group-hover:text-white group-hover:shadow-xs">
                        <Shield class="w-5 h-5 text-[#10B981] group-hover:text-white transition-colors" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111] transition-colors group-hover:text-black">
                            {{ currentLocale === 'km' ? 'ទំនិញសុទ្ធ ១០០%' : '100% Authentic' }}
                        </h4>
                        <p class="text-[11px] text-[#86868B]">
                            {{ currentLocale === 'km' ? 'ធានាគុណភាពផ្លូវការ' : 'Official Warranty Guarantee' }}
                        </p>
                    </div>
                </div>

                <div class="group flex items-center gap-3.5 p-3 rounded-2xl transition-all duration-200 hover:bg-[#F5F5F7]/80 cursor-default">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111] shrink-0 transition-all duration-300 ease-out group-hover:scale-110 group-hover:-rotate-3 group-hover:bg-[#111111] group-hover:text-white group-hover:shadow-xs">
                        <Truck class="w-5 h-5 text-[#0071E3] group-hover:text-white transition-colors" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111] transition-colors group-hover:text-black">
                            {{ currentLocale === 'km' ? 'ដឹកជញ្ជូនរហ័ស' : 'Express Delivery' }}
                        </h4>
                        <p class="text-[11px] text-[#86868B]">
                            {{ currentLocale === 'km' ? 'ភ្នំពេញ និង ២៤ ខេត្ត' : 'Phnom Penh & 24 Provinces' }}
                        </p>
                    </div>
                </div>

                <div class="group flex items-center gap-3.5 p-3 rounded-2xl transition-all duration-200 hover:bg-[#F5F5F7]/80 cursor-default">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111] shrink-0 transition-all duration-300 ease-out group-hover:scale-110 group-hover:-rotate-3 group-hover:bg-[#111111] group-hover:text-white group-hover:shadow-xs">
                        <Sparkles class="w-5 h-5 text-[#D4AF37] group-hover:text-white transition-colors" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111] transition-colors group-hover:text-black">
                            {{ currentLocale === 'km' ? 'បាគង KHQR ផ្លូវការ' : 'Bakong KHQR' }}
                        </h4>
                        <p class="text-[11px] text-[#86868B]">
                            {{ currentLocale === 'km' ? 'ទូទាត់រហ័ស សុវត្ថិភាព' : 'Seamless Mobile Payments' }}
                        </p>
                    </div>
                </div>

                <div class="group flex items-center gap-3.5 p-3 rounded-2xl transition-all duration-200 hover:bg-[#F5F5F7]/80 cursor-default">
                    <div class="p-2.5 rounded-xl bg-[#F5F5F7] text-[#111111] shrink-0 transition-all duration-300 ease-out group-hover:scale-110 group-hover:-rotate-3 group-hover:bg-[#111111] group-hover:text-white group-hover:shadow-xs">
                        <Headphones class="w-5 h-5 text-[#86868B] group-hover:text-white transition-colors" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#111111] transition-colors group-hover:text-black">
                            {{ currentLocale === 'km' ? 'សេវាអតិថិជន ២៤/៧' : 'Client Concierge' }}
                        </h4>
                        <p class="text-[11px] text-[#86868B]">
                            {{ currentLocale === 'km' ? 'ជំនួយការទិញផ្ទាល់ខ្លួន' : 'Dedicated Support 7 Days' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Curated Categories Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-8 py-16">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111111] tracking-tight">
                        {{ t('home.featured_categories') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-1">
                        {{ t('home.featured_subtitle') }}
                    </p>
                </div>
                <Link href="/shop" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-[#0071E3] hover:text-[#0077ED] transition-colors group">
                    <span>{{ currentLocale === 'km' ? 'មើលទាំងអស់' : 'View All' }}</span>
                    <ArrowRight class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
                <Link
                    v-for="cat in categories"
                    :key="cat.id"
                    :href="`/shop?category=${cat.slug}`"
                    class="group relative bg-white rounded-2xl border border-[#E5E5EA] p-6 text-center overflow-hidden d-card-hover hover:border-[#111111] active:scale-[0.98]"
                >
                    <div class="aspect-square w-16 h-16 mx-auto mb-4 rounded-2xl bg-[#F5F5F7] flex items-center justify-center transition-all duration-300 ease-out group-hover:scale-110 group-hover:bg-[#111111] group-hover:text-white group-hover:shadow-md">
                        <Sparkles class="w-6 h-6 text-[#111111] group-hover:text-white transition-colors duration-300" />
                    </div>
                    <h3 class="text-sm font-bold text-[#1D1D1F] group-hover:text-[#0071E3] transition-colors duration-200">
                        {{ currentLocale === 'km' && cat.name_km ? cat.name_km : cat.name_en }}
                    </h3>
                </Link>
            </div>
        </section>

        <!-- Trending Products Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-8 py-12">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#111111]/[0.05] text-[#111111] text-[10.5px] font-bold uppercase tracking-wider mb-2">
                        <Sparkles class="w-3 h-3 text-[#0071E3]" />
                        <span>{{ currentLocale === 'km' ? 'ទំនិញពេញនិយម' : 'Curated Selection' }}</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111111] tracking-tight">
                        {{ t('home.trending_products') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-1">
                        {{ t('home.trending_subtitle') }}
                    </p>
                </div>
                <Link href="/shop" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-[#0071E3] hover:text-[#0077ED] transition-colors group">
                    <span>{{ currentLocale === 'km' ? 'ស្វែងរកកាតាឡុក' : 'Explore Catalog' }}</span>
                    <ArrowRight class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" />
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
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#111111]/[0.05] text-[#111111] text-[10.5px] font-bold uppercase tracking-wider mb-2">
                        <Zap class="w-3 h-3 text-[#10B981]" />
                        <span>{{ currentLocale === 'km' ? 'មកដល់ថ្មីៗ' : 'Exclusive Drop' }}</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111111] tracking-tight">
                        {{ t('home.new_arrivals') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#86868B] mt-1">
                        {{ currentLocale === 'km' ? 'ទំនិញទើបមកដល់ថ្មីក្នុងបណ្តុំទំនិញរាជធានីភ្នំពេញ' : 'Latest editions added to our Phnom Penh collection.' }}
                    </p>
                </div>
                <Link href="/shop" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-[#0071E3] hover:text-[#0077ED] transition-colors group">
                    <span>{{ currentLocale === 'km' ? 'មើលទាំងអស់' : 'View All' }}</span>
                    <ArrowRight class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" />
                </Link>
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
                        {{ currentLocale === 'km' ? 'ការវាយតម្លៃពិតប្រាកដពីអតិថិជនទូទាំងប្រទេសកម្ពុជា។' : 'Authentic experiences from verified purchasers across Cambodia.' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        v-for="rev in reviews"
                        :key="rev.id"
                        class="bg-[#F5F5F7] p-8 rounded-3xl border border-[#E5E5EA] space-y-4 shadow-2xs d-card-hover"
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

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 800ms cubic-bezier(0.23, 1, 0.32, 1);
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.hero-text-enter-active {
    transition: opacity 350ms cubic-bezier(0.16, 1, 0.3, 1),
                transform 350ms cubic-bezier(0.16, 1, 0.3, 1),
                filter 350ms cubic-bezier(0.16, 1, 0.3, 1);
}
.hero-text-leave-active {
    transition: opacity 180ms cubic-bezier(0.7, 0, 0.84, 0),
                transform 180ms cubic-bezier(0.7, 0, 0.84, 0),
                filter 180ms ease;
}
.hero-text-enter-from {
    opacity: 0;
    transform: translateY(10px);
    filter: blur(2px);
}
.hero-text-leave-to {
    opacity: 0;
    transform: translateY(-8px);
    filter: blur(2px);
}
</style>
