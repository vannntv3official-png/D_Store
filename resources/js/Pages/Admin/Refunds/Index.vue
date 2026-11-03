<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Textarea from '@/Components/Textarea.vue';
import Dialog from '@/Components/Dialog.vue';
import Badge from '@/Components/Badge.vue';
import { Plus, RotateCcw, CheckCircle2, ShieldAlert, PackageCheck } from 'lucide-vue-next';

interface RefundItem {
    id: number;
    order_id: number;
    order_number: string;
    customer_name: string;
    amount: string;
    reason: string;
    status: string;
    is_restocked: boolean;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    refunds: {
        data: RefundItem[];
        links: PaginationLink[];
        total: number;
    };
}

defineProps<Props>();

const showModal = ref(false);
const isSubmitting = ref(false);
const inspectingId = ref<number | null>(null);
const errors = ref<Record<string, string>>({});

const form = reactive({
    order_id: '',
    amount: '',
    reason: '',
});

const submitRefund = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post('/admin/refunds', form, {
        onSuccess: () => {
            showModal.value = false;
            form.order_id = '';
            form.amount = '';
            form.reason = '';
        },
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const handleInspectAndRestock = (id: number) => {
    if (confirm('Verify physical item condition and restock inventory to available stock?')) {
        inspectingId.value = id;
        router.post(`/admin/refunds/${id}/inspect-and-restock`, {}, {
            onFinish: () => {
                inspectingId.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Refunds & Returns — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Refunds & Returns Ledger</h1>
                    <p class="text-xs text-[#86868B] mt-1">Authorized refund disbursements, loyalty clawbacks, and physical restock audits.</p>
                </div>
                <Button variant="primary" size="sm" @click="showModal = true" class="flex items-center gap-1.5">
                    <Plus class="w-3.5 h-3.5" />
                    <span>Issue Refund</span>
                </Button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Refund ID</th>
                                <th class="py-3.5 px-6">Order</th>
                                <th class="py-3.5 px-6">Customer</th>
                                <th class="py-3.5 px-6">Refund Amount</th>
                                <th class="py-3.5 px-6">Reason</th>
                                <th class="py-3.5 px-6">Restock Status</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="refunds.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-[#86868B]">
                                    No refund records found.
                                </td>
                            </tr>
                            <tr
                                v-for="rf in refunds.data"
                                :key="rf.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-mono text-[#86868B]">
                                    #RF-{{ rf.id }}
                                </td>
                                <td class="py-4 px-6">
                                    <Link :href="`/admin/orders/${rf.order_id}`" class="font-bold text-[#111111] hover:underline">
                                        {{ rf.order_number }}
                                    </Link>
                                    <div class="text-[10px] text-[#86868B]">{{ rf.created_at }}</div>
                                </td>
                                <td class="py-4 px-6 font-medium text-[#1D1D1F]">
                                    {{ rf.customer_name }}
                                </td>
                                <td class="py-4 px-6 font-black text-[#DC2626]">
                                    -${{ rf.amount }}
                                </td>
                                <td class="py-4 px-6 text-[#86868B] max-w-xs truncate" :title="rf.reason">
                                    {{ rf.reason }}
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="rf.is_restocked ? 'success' : 'warning'">
                                        {{ rf.is_restocked ? 'Restocked' : 'Pending Inspection' }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <Button
                                        v-if="!rf.is_restocked"
                                        size="sm"
                                        variant="secondary"
                                        :loading="inspectingId === rf.id"
                                        @click="handleInspectAndRestock(rf.id)"
                                        class="text-xs"
                                    >
                                        <PackageCheck class="w-3.5 h-3.5 mr-1" />
                                        <span>Inspect & Restock</span>
                                    </Button>
                                    <span v-else class="text-[11px] text-[#86868B] inline-flex items-center gap-1">
                                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                                        Restocked
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="refunds.links.length > 3" class="p-4 border-t border-[#F5F5F7] flex items-center justify-between">
                    <p class="text-xs text-[#86868B]">
                        Showing {{ refunds.data.length }} of {{ refunds.total }} refunds
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in refunds.links"
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

        <!-- Issue Refund Dialog -->
        <Dialog :open="showModal" @close="showModal = false" title="Authorize Disbursement Refund">
            <form @submit.prevent="submitRefund" class="space-y-4">
                <Input
                    v-model="form.order_id"
                    type="number"
                    label="Order ID (Numeric Database ID)"
                    placeholder="e.g. 1"
                    :error="errors.order_id"
                    required
                />
                <Input
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    label="Refund Amount (USD)"
                    placeholder="0.00"
                    :error="errors.amount"
                    required
                />
                <Textarea
                    v-model="form.reason"
                    label="Authorized Audit Reason"
                    placeholder="e.g. Client requested cancellation before shipping..."
                    :error="errors.reason"
                    required
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="danger" :loading="isSubmitting">
                        Authorize Refund
                    </Button>
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
