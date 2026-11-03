<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import { ArrowLeft, ArrowUpRight, ArrowDownLeft, ShieldCheck } from 'lucide-vue-next';

interface MovementRecord {
    id: number;
    sku: string;
    product_name: string;
    quantity_change: number;
    resulting_on_hand: number;
    resulting_reserved: number;
    reason: string;
    actor: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    movements: {
        data: MovementRecord[];
        links: PaginationLink[];
        total: number;
    };
}

defineProps<Props>();
</script>

<template>
    <AdminLayout>
        <Head title="Inventory Audit Movements — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/admin/inventory" class="p-2 rounded-xl bg-white border border-[#E5E5EA] text-[#86868B] hover:text-[#111111] transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black text-[#111111] tracking-tight">Stock Movement Audit Trail</h1>
                        <p class="text-xs text-[#86868B] mt-0.5">Immutable append-only ledger of every inventory increment, reservation, and shipment deduction.</p>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Timestamp</th>
                                <th class="py-3.5 px-6">Product / SKU</th>
                                <th class="py-3.5 px-6 text-center">Delta</th>
                                <th class="py-3.5 px-6 text-center">New On Hand</th>
                                <th class="py-3.5 px-6 text-center">New Reserved</th>
                                <th class="py-3.5 px-6">Reason / Trigger</th>
                                <th class="py-3.5 px-6">Actor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="movements.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-[#86868B]">
                                    No stock movements recorded yet.
                                </td>
                            </tr>
                            <tr
                                v-for="m in movements.data"
                                :key="m.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 text-[#86868B] whitespace-nowrap font-mono text-[11px]">
                                    {{ m.created_at }}
                                </td>
                                <td class="py-4 px-6 font-medium">
                                    <div class="font-bold text-[#111111]">{{ m.product_name }}</div>
                                    <div class="font-mono text-[10px] text-[#86868B]">SKU: {{ m.sku }}</div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center font-bold px-2 py-0.5 rounded-full text-xs',
                                            m.quantity_change > 0
                                                ? 'bg-emerald-50 text-emerald-600'
                                                : m.quantity_change < 0
                                                ? 'bg-rose-50 text-rose-600'
                                                : 'bg-neutral-100 text-neutral-600'
                                        ]"
                                    >
                                        {{ m.quantity_change > 0 ? `+${m.quantity_change}` : m.quantity_change }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-[#111111]">
                                    {{ m.resulting_on_hand }}
                                </td>
                                <td class="py-4 px-6 text-center font-medium text-amber-600">
                                    {{ m.resulting_reserved }}
                                </td>
                                <td class="py-4 px-6 text-[#1D1D1F] font-medium">
                                    {{ m.reason }}
                                </td>
                                <td class="py-4 px-6 text-[#86868B]">
                                    {{ m.actor }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="movements.links.length > 3" class="p-4 border-t border-[#F5F5F7] flex items-center justify-between">
                    <p class="text-xs text-[#86868B]">
                        Showing {{ movements.data.length }} of {{ movements.total }} movement records
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in movements.links"
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
