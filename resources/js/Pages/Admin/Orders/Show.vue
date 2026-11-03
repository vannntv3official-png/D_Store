<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import Dialog from '@/Components/Dialog.vue';
import Textarea from '@/Components/Textarea.vue';
import { 
    ArrowLeft, Printer, Truck, DollarSign, User, MapPin, 
    CheckCircle2, Clock, AlertCircle, RotateCcw 
} from 'lucide-vue-next';

interface OrderItem {
    id: number;
    product_name: string;
    sku: string;
    quantity: number;
    unit_price: string;
    subtotal: string;
}

interface PaymentRecord {
    id: number;
    method: string;
    status: string;
    amount: string;
    created_at: string;
}

interface ShipmentRecord {
    id: number;
    carrier: string;
    tracking_number: string;
    status: string;
    shipped_at?: string | null;
    delivered_at?: string | null;
}

interface TimelineEvent {
    from: string;
    to: string;
    comment?: string | null;
    actor: string;
    created_at: string;
}

interface RefundRecord {
    id: number;
    amount: string;
    reason: string;
    status: string;
    created_at: string;
}

interface OrderDetail {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    subtotal: string;
    discount: string;
    shipping_fee: string;
    total_amount: string;
    total_khr: string;
    created_at: string;
    customer: {
        id?: number | null;
        name: string;
        email?: string | null;
        phone: string;
    };
    shipping_address?: {
        recipient_name: string;
        phone: string;
        street_address: string;
        district: string;
        commune?: string | null;
        province: string;
    } | null;
    items: OrderItem[];
    payments: PaymentRecord[];
    shipments: ShipmentRecord[];
    timeline: TimelineEvent[];
    refunds: RefundRecord[];
}

interface Props {
    order: OrderDetail;
    allowedTransitions: string[];
}

const props = defineProps<Props>();

const showTransitionModal = ref(false);
const selectedToStatus = ref('');
const transitionComment = ref('');
const isSubmittingTransition = ref(false);

const openTransitionModal = (status: string) => {
    selectedToStatus.value = status;
    transitionComment.value = '';
    showTransitionModal.value = true;
};

const executeTransition = () => {
    isSubmittingTransition.value = true;
    router.post(
        `/admin/orders/${props.order.id}/transition`,
        {
            to_status: selectedToStatus.value,
            comment: transitionComment.value || undefined,
        },
        {
            onSuccess: () => {
                showTransitionModal.value = false;
            },
            onFinish: () => {
                isSubmittingTransition.value = false;
            },
        }
    );
};

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'delivered':
        case 'paid':
            return 'success';
        case 'shipped':
        case 'processing':
        case 'confirmed':
        case 'packed':
            return 'blue';
        case 'pending':
            return 'warning';
        case 'cancelled':
        case 'failed':
            return 'danger';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Order #${order.order_number} — D STORE`" />

        <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/admin/orders" class="p-2 rounded-xl bg-white border border-[#E5E5EA] text-[#86868B] hover:text-[#111111] transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-black text-[#111111] tracking-tight">
                                Order #{{ order.order_number }}
                            </h1>
                            <Badge :variant="getStatusBadgeVariant(order.status)">
                                {{ order.status }}
                            </Badge>
                            <Badge :variant="getStatusBadgeVariant(order.payment_status)">
                                Payment: {{ order.payment_status }}
                            </Badge>
                        </div>
                        <p class="text-xs text-[#86868B] mt-1">Placed on {{ order.created_at }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="`/admin/orders/${order.id}/invoice`" target="_blank">
                        <Button variant="secondary" size="sm" class="flex items-center gap-1.5">
                            <Printer class="w-3.5 h-3.5" />
                            <span>Print Thermal/A4 Invoice</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Lifecycle Actions Bar -->
            <div
                v-if="allowedTransitions.length > 0"
                class="bg-white rounded-2xl p-4 sm:p-5 border border-[#E5E5EA] shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-2">
                    <Clock class="w-4 h-4 text-[#86868B]" />
                    <span class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                        Permitted State Machine Transitions:
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        v-for="st in allowedTransitions"
                        :key="st"
                        size="sm"
                        :variant="st === 'cancelled' ? 'danger' : 'primary'"
                        @click="openTransitionModal(st)"
                        class="capitalize"
                    >
                        Advance to: {{ st }}
                    </Button>
                </div>
            </div>

            <!-- Content 2-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left 8 cols: Line Items & Financial Summary -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Purchased Items -->
                    <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#F5F5F7]">
                            <h2 class="text-sm font-black text-[#111111] uppercase tracking-wider">
                                Purchased Items ({{ order.items.length }})
                            </h2>
                        </div>

                        <div class="divide-y divide-[#F5F5F7]">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="p-6 flex items-center justify-between"
                            >
                                <div>
                                    <h4 class="text-xs font-bold text-[#111111]">{{ item.product_name }}</h4>
                                    <p class="text-[11px] text-[#86868B] font-mono mt-0.5">SKU: {{ item.sku }}</p>
                                    <p class="text-[11px] text-[#86868B] mt-0.5">
                                        ${{ item.unit_price }} &times; {{ item.quantity }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-[#111111]">${{ item.subtotal }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Totals -->
                        <div class="p-6 bg-[#FBFBFC] border-t border-[#F5F5F7] space-y-2 text-xs">
                            <div class="flex justify-between text-[#86868B]">
                                <span>Subtotal</span>
                                <span class="font-medium text-[#111111]">${{ order.subtotal }}</span>
                            </div>
                            <div v-if="order.discount !== '0.00'" class="flex justify-between text-emerald-600 font-medium">
                                <span>Discounts Applied</span>
                                <span>-${{ order.discount }}</span>
                            </div>
                            <div class="flex justify-between text-[#86868B]">
                                <span>Delivery Fee</span>
                                <span class="font-medium text-[#111111]">${{ order.shipping_fee }}</span>
                            </div>
                            <div class="pt-3 border-t border-[#E5E5EA] flex justify-between items-baseline">
                                <span class="text-sm font-bold text-[#111111]">Grand Total</span>
                                <div class="text-right">
                                    <div class="text-lg font-black text-[#111111]">${{ order.total_amount }}</div>
                                    <div class="text-[11px] text-[#86868B]">៛{{ order.total_khr }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Lifecycle Audit Timeline -->
                    <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm space-y-4">
                        <h2 class="text-sm font-black text-[#111111] uppercase tracking-wider mb-2">
                            Audit State Transitions Timeline
                        </h2>

                        <div v-if="order.timeline.length === 0" class="text-xs text-[#86868B]">
                            Initial order placed. No subsequent status transitions logged yet.
                        </div>

                        <div v-else class="space-y-4 pl-2 border-l-2 border-[#111111]/20">
                            <div
                                v-for="(evt, idx) in order.timeline"
                                :key="idx"
                                class="relative pl-6"
                            >
                                <div class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-[#111111] border-2 border-white"></div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-[#111111]">
                                        {{ evt.from }} &rarr; {{ evt.to }}
                                    </span>
                                    <span class="text-[10px] text-[#86868B] font-medium">by {{ evt.actor }}</span>
                                </div>
                                <p v-if="evt.comment" class="text-xs text-[#1D1D1F] mt-1 italic">
                                    &ldquo;{{ evt.comment }}&rdquo;
                                </p>
                                <p class="text-[10px] text-[#86868B] mt-0.5">{{ evt.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right 4 cols: Customer, Shipping, Payments -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Customer Information -->
                    <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#111111]">
                            <User class="w-4 h-4 text-[#86868B]" />
                            <span>Customer Profile</span>
                        </div>
                        <div class="text-xs space-y-1">
                            <p class="font-bold text-[#111111]">{{ order.customer.name }}</p>
                            <p class="text-[#86868B] font-mono">{{ order.customer.phone }}</p>
                            <p v-if="order.customer.email" class="text-[#86868B]">{{ order.customer.email }}</p>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#111111]">
                            <MapPin class="w-4 h-4 text-[#86868B]" />
                            <span>Delivery Destination</span>
                        </div>
                        <div v-if="order.shipping_address" class="text-xs leading-relaxed text-[#1D1D1F]">
                            <p class="font-bold text-[#111111]">{{ order.shipping_address.recipient_name }}</p>
                            <p class="text-[#86868B]">{{ order.shipping_address.phone }}</p>
                            <p class="mt-2 text-[#86868B]">
                                {{ order.shipping_address.street_address }}<br />
                                <span v-if="order.shipping_address.commune">{{ order.shipping_address.commune }}, </span>
                                {{ order.shipping_address.district }}<br />
                                <strong class="text-[#111111]">{{ order.shipping_address.province }}, Cambodia</strong>
                            </p>
                        </div>
                        <div v-else class="text-xs text-[#86868B]">
                            In-store pickup / direct collection
                        </div>
                    </div>

                    <!-- Payment Gateway Settlement -->
                    <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#111111]">
                            <DollarSign class="w-4 h-4 text-[#86868B]" />
                            <span>Settlement & Payments</span>
                        </div>

                        <div v-if="order.payments.length === 0" class="text-xs text-[#86868B]">
                            Awaiting settlement capture.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="pmt in order.payments"
                                :key="pmt.id"
                                class="p-3 rounded-xl bg-[#FBFBFC] border border-[#E5E5EA] text-xs space-y-1"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-[#111111] uppercase">{{ pmt.method }}</span>
                                    <Badge :variant="getStatusBadgeVariant(pmt.status)">
                                        {{ pmt.status }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between text-[#86868B] pt-1">
                                    <span>Amount Settled</span>
                                    <span class="font-bold text-[#111111]">${{ pmt.amount }}</span>
                                </div>
                                <div class="text-[10px] text-[#86868B]">{{ pmt.created_at }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transition Confirmation Dialog -->
        <Dialog
            :open="showTransitionModal"
            @close="showTransitionModal = false"
            :title="`Advance Fulfillment to: ${selectedToStatus.toUpperCase()}`"
        >
            <div class="space-y-4">
                <p class="text-xs text-[#86868B]">
                    Please provide an audit comment or reason for this status change. This will be preserved in the order's immutable history ledger.
                </p>

                <Textarea
                    v-model="transitionComment"
                    label="Audit Comment / Tracking Notes (Optional)"
                    rows="3"
                    placeholder="e.g. Courier picked up package for delivery..."
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button variant="secondary" @click="showTransitionModal = false">
                        Cancel
                    </Button>
                    <Button variant="primary" :loading="isSubmittingTransition" @click="executeTransition">
                        Confirm Transition
                    </Button>
                </div>
            </div>
        </Dialog>
    </AdminLayout>
</template>
