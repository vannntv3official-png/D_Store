<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, Package, FolderTree, ShoppingCart, 
    RotateCcw, Warehouse, Megaphone, Award, Users, 
    Settings, ExternalLink, LogOut, History
} from 'lucide-vue-next';

const page = usePage();
const auth = computed(() => (page.props as any).auth || {});
const flash = computed(() => (page.props as any).flash || {});

const currentUrl = computed(() => page.url);

const navItems = [
    { name: 'Dashboard', href: '/admin', icon: LayoutDashboard },
    { name: 'Products', href: '/admin/products', icon: Package },
    { name: 'Categories', href: '/admin/categories', icon: FolderTree },
    { name: 'Orders', href: '/admin/orders', icon: ShoppingCart },
    { name: 'Refunds', href: '/admin/refunds', icon: RotateCcw },
    { name: 'Inventory', href: '/admin/inventory', icon: Warehouse },
    { name: 'Stock Audit', href: '/admin/inventory/movements', icon: History },
    { name: 'Marketing & Banners', href: '/admin/marketing', icon: Megaphone },
    { name: 'Loyalty Rewards', href: '/admin/loyalty', icon: Award },
    { name: 'Staff & Roles', href: '/admin/staff', icon: Users },
    { name: 'Store Settings', href: '/admin/settings', icon: Settings },
];

const logout = () => {
    router.post('/admin/logout');
};
</script>

<template>
    <div class="min-h-screen flex bg-[#F5F5F7] text-[#1D1D1F]">
        <!-- Collapsible Dark Sidebar -->
        <aside class="w-64 bg-[#111111] text-white flex flex-col shrink-0 border-r border-white/10">
            <!-- Brand Portal Header -->
            <div class="h-18 px-6 flex items-center justify-between border-b border-white/10">
                <Link href="/admin" class="flex items-center gap-2">
                    <span class="text-base font-black tracking-tight text-white">D STORE</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-white/20 text-white">
                        PORTAL
                    </span>
                </Link>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-colors"
                    :class="[
                        currentUrl === item.href || (item.href !== '/admin' && currentUrl.startsWith(item.href))
                            ? 'bg-white text-[#111111]'
                            : 'text-[#86868B] hover:text-white hover:bg-white/5',
                    ]"
                >
                    <component :is="item.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Staff Footer Profile -->
            <div class="p-4 border-t border-white/10 bg-black/40">
                <div class="flex items-center justify-between mb-2">
                    <div class="truncate">
                        <p class="text-xs font-bold text-white truncate">{{ auth.user?.name || 'Staff' }}</p>
                        <p class="text-[10px] text-[#86868B] truncate">{{ auth.user?.email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-2 border-t border-white/10">
                    <Link
                        href="/"
                        target="_blank"
                        class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-white/10 text-white text-[11px] font-semibold hover:bg-white/20 transition-colors"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                        <span>Storefront</span>
                    </Link>
                    <button
                        type="button"
                        @click="logout"
                        class="p-1.5 rounded-lg text-[#DC2626] hover:bg-[#DC2626]/20 transition-colors cursor-pointer"
                        title="Sign Out"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Top Admin Header -->
            <header class="h-18 bg-white border-b border-[#E5E5EA] px-8 flex items-center justify-between">
                <div>
                    <h1 class="text-base font-bold text-[#111111]">
                        <slot name="header">Administrative Portal</slot>
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <Link
                        href="/"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[#E5E5EA] text-xs font-semibold text-[#1D1D1F] hover:bg-[#F5F5F7] transition-colors"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                        <span>View Live Store</span>
                    </Link>
                </div>
            </header>

            <!-- Flash Alert Messages -->
            <div v-if="flash.success || flash.error" class="px-8 mt-6">
                <div
                    v-if="flash.success"
                    class="p-4 rounded-xl bg-[#DCFCE7] border border-[#BBF7D0] text-[#166534] text-xs font-semibold"
                >
                    {{ flash.success }}
                </div>
                <div
                    v-if="flash.error"
                    class="p-4 rounded-xl bg-[#FEE2E2] border border-[#FECACA] text-[#991B1B] text-xs font-semibold"
                >
                    {{ flash.error }}
                </div>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
