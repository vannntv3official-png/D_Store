<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ShoppingBag, User, MapPin, Award, Heart } from 'lucide-vue-next';
import { useTrans } from '@/Composables/useTrans';

const page = usePage();
const { t } = useTrans();

const tabs = [
    { name: 'My Orders', href: '/account/orders', icon: ShoppingBag },
    { name: 'Profile', href: '/account/profile', icon: User },
    { name: 'Addresses', href: '/account/addresses', icon: MapPin },
    { name: 'Loyalty & Rewards', href: '/account/loyalty', icon: Award },
    { name: 'Wishlist', href: '/account/wishlist', icon: Heart },
];

const isActive = (href: string) => page.url.startsWith(href);
</script>

<template>
    <nav class="flex items-center gap-2 overflow-x-auto pb-4 mb-8 border-b border-[#E5E5EA] scrollbar-none">
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            :class="[
                'flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all duration-200',
                isActive(tab.href)
                    ? 'bg-[#111111] text-white shadow-sm'
                    : 'text-[#86868B] hover:text-[#111111] hover:bg-[#F5F5F7]'
            ]"
        >
            <component :is="tab.icon" class="w-3.5 h-3.5" />
            <span>{{ tab.name }}</span>
        </Link>
    </nav>
</template>
