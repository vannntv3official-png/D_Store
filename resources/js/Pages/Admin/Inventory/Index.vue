<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Dialog from '@/Components/Dialog.vue';
import Badge from '@/Components/Badge.vue';
import { Search, History, SlidersHorizontal, AlertTriangle } from 'lucide-vue-next';

interface StockItem {
    id: number;
    variant_id: number;
    sku: string;
    product_name: string;
    on_hand: number;
    reserved: number;
    available: number;
    updated_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    stockItems: {
        data: StockItem[];
        links: PaginationLink[];
        total: number;
    };
    filters: {
        q?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.q || '');
const showAdjustModal = ref(false);
const activeItem = ref<StockItem | null>(null);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const adjustForm = reactive({
    variant_id: 0,
    delta: 0,
    reason: '',
});

const applySearch = () => {
    router.get(
        '/admin/inventory',
        { q: search.value || undefined },
        { preserveState: true, replace: true }
    );
};

const openAdjust = (item: StockItem) => {
    activeItem.value = item;
    adjustForm.variant_id = item.variant_id;
    adjustForm.delta = 0;
    adjustForm.reason = '';
    errors.value = {};
    showAdjustModal.value = true;
};

const submitAdjust = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post('/admin/inventory/adjust', adjustForm, {
        onSuccess: () => {
            showAdjustModal.value = false;
        },
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Inventory & Stock Management — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Inventory & Stock Levels</h1>
                    <p class="text-xs text-[#86868B] mt-1">Single warehouse stock balances with pessimistic lock protection and reservations.</p>
                </div>
                <Link href="/admin/inventory/movements">
                    <Button variant="secondary" size="sm" class="flex items-center gap-1.5">
                        <History class="w-3.5 h-3.5" />
                        <span>Audit Movement Logs</span>
                    </Button>
                </Link>
            </div>

            <!-- Search -->
            <div class="bg-white p-4 rounded-2xl border border-[#E5E5EA] shadow-sm flex items-center gap-3">
                <div class="relative flex-1">
                    <Search class="w-4 h-4 text-[#86868B] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="search"
                        @keyup.enter="applySearch"
                        placeholder="Search SKU or product title..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-[#F5F5F7] border border-transparent rounded-xl focus:bg-white focus:border-[#111111] outline-none transition-all"
                    />
                </div>
                <Button variant="secondary" size="sm" @click="applySearch">
                    Search
                </Button>
            </div>

            <!-- Stock Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">SKU / Variant</th>
                                <th class="py-3.5 px-6">Product</th>
                                <th class="py-3.5 px-6 text-center">On Hand</th>
                                <th class="py-3.5 px-6 text-center">Reserved</th>
                                <th class="py-3.5 px-6 text-center">Available</th>
                                <th class="py-3.5 px-6">Last Synchronized</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="stockItems.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-[#86868B]">
                                    No stock items located.
                                </td>
                            </tr>
                            <tr
                                v-for="item in stockItems.data"
                                :key="item.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-mono font-bold text-[#111111]">
                                    {{ item.sku }}
                                </td>
                                <td class="py-4 px-6 font-medium text-[#1D1D1F]">
                                    {{ item.product_name }}
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-[#111111]">
                                    {{ item.on_hand }}
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-amber-600">
                                    {{ item.reserved }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span
                                        :class="[
                                            'font-black text-sm',
                                            item.available <= 5 ? 'text-[#DC2626]' : 'text-emerald-600'
                                        ]"
                                    >
                                        {{ item.available }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-[#86868B]">
                                    {{ item.updated_at }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="openAdjust(item)"
                                        class="flex items-center gap-1 ml-auto"
                                    >
                                        <SlidersHorizontal class="w-3.5 h-3.5" />
                                        <span>Adjust</span>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="stockItems.links.length > 3" class="p-4 border-t border-[#F5F5F7] flex items-center justify-between">
                    <p class="text-xs text-[#86868B]">
                        Showing {{ stockItems.data.length }} of {{ stockItems.total }} stock records
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in stockItems.links"
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

        <!-- Adjust Stock Modal -->
        <Dialog
            :open="showAdjustModal"
            @close="showAdjustModal = false"
            title="Manual Warehouse Stock Adjustment"
        >
            <div v-if="activeItem" class="space-y-4">
                <div class="p-3 bg-[#FBFBFC] rounded-xl border border-[#E5E5EA] text-xs">
                    <p class="font-bold text-[#111111]">{{ activeItem.product_name }}</p>
                    <p class="text-[#86868B] font-mono mt-0.5">SKU: {{ activeItem.sku }} • Current On Hand: {{ activeItem.on_hand }}</p>
                </div>

                <form @submit.prevent="submitAdjust" class="space-y-4">
                    <Input
                        v-model="adjustForm.delta"
                        type="number"
                        label="Quantity Delta (+ for stock arrival, - for damage/loss)"
                        placeholder="e.g. 10 or -2"
                        :error="errors.delta"
                        required
                    />

                    <Input
                        v-model="adjustForm.reason"
                        label="Audit Reason / Invoice Reference"
                        placeholder="e.g. Shipment arrival PO #4092, Cycle count audit"
                        :error="errors.reason"
                        required
                    />

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                        <Button type="button" variant="secondary" @click="showAdjustModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" :loading="isSubmitting">
                            Record Adjustment
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>
    </AdminLayout>
</template>
