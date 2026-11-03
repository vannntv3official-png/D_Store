<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Textarea from '@/Components/Textarea.vue';
import { useTrans } from '@/Composables/useTrans';
import { Mail, Phone, MapPin, Clock, Send, MessageSquareCheck } from 'lucide-vue-next';

interface StoreInfo {
    name: string;
    phone: string;
    email: string;
    address_en: string;
    address_km: string;
}

interface Props {
    storeInfo: StoreInfo;
}

const props = defineProps<Props>();
const { t, currentLocale } = useTrans();

const form = reactive({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const isSubmitting = ref(false);
const submitted = ref(false);
const errors = ref<Record<string, string>>({});

const submitForm = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post('/contact', form, {
        onSuccess: () => {
            submitted.value = true;
            form.name = '';
            form.email = '';
            form.phone = '';
            form.subject = '';
            form.message = '';
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
    <CustomerLayout>
        <Head title="Contact Concierge — D STORE" />

        <div class="max-w-6xl mx-auto px-4 sm:px-8 py-16">
            <div class="max-w-2xl mb-12">
                <span class="text-xs font-bold uppercase tracking-wider text-[#0071E3] mb-2 block">
                    Concierge & Client Care
                </span>
                <h1 class="text-4xl sm:text-5xl font-black text-[#111111] tracking-tight">
                    We Are Here to Assist.
                </h1>
                <p class="text-sm text-[#86868B] mt-3 leading-relaxed">
                    Have questions regarding custom orders, delivery schedules across Cambodia, or corporate inquiries? Connect with our dedicated advisory team.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Concierge Info -->
                <div class="lg:col-span-5 space-y-8">
                    <div class="bg-white rounded-3xl p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                            Boutique Headquarters
                        </h2>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-[#F5F5F7] flex items-center justify-center shrink-0 text-[#111111]">
                                <MapPin class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-[#111111]">Showroom Location</h3>
                                <p class="text-xs text-[#86868B] mt-1 leading-relaxed">
                                    {{ currentLocale === 'km' ? storeInfo.address_km : storeInfo.address_en }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-[#F5F5F7] flex items-center justify-center shrink-0 text-[#111111]">
                                <Phone class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-[#111111]">Direct Line / Telegram</h3>
                                <p class="text-xs text-[#86868B] mt-1 font-medium">
                                    {{ storeInfo.phone }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-[#F5F5F7] flex items-center justify-center shrink-0 text-[#111111]">
                                <Mail class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-[#111111]">Email Advisory</h3>
                                <p class="text-xs text-[#86868B] mt-1">
                                    {{ storeInfo.email }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 pt-4 border-t border-[#F5F5F7]">
                            <div class="w-10 h-10 rounded-2xl bg-[#F5F5F7] flex items-center justify-center shrink-0 text-[#111111]">
                                <Clock class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-[#111111]">Operating Hours</h3>
                                <p class="text-xs text-[#86868B] mt-1 leading-relaxed">
                                    Monday – Sunday<br />
                                    08:00 AM – 08:00 PM (ICT Phnom Penh)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Message Form -->
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-3xl p-8 sm:p-10 border border-[#E5E5EA] shadow-sm">
                        <div v-if="submitted" class="py-12 text-center space-y-4">
                            <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
                                <MessageSquareCheck class="w-7 h-7" />
                            </div>
                            <h3 class="text-xl font-bold text-[#111111]">Message Delivered</h3>
                            <p class="text-xs text-[#86868B] max-w-md mx-auto">
                                Thank you for contacting D STORE. Our client concierge has received your request and will respond within 24 hours.
                            </p>
                            <Button variant="secondary" @click="submitted = false">
                                Send Another Message
                            </Button>
                        </div>

                        <form v-else @submit.prevent="submitForm" class="space-y-5">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111] mb-2">
                                Send a Direct Message
                            </h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Input
                                    v-model="form.name"
                                    label="Your Full Name"
                                    placeholder="e.g. Chan Dara"
                                    :error="errors.name"
                                    required
                                />
                                <Input
                                    v-model="form.phone"
                                    label="Phone Number"
                                    placeholder="e.g. 012 345 678"
                                    :error="errors.phone"
                                    required
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Input
                                    v-model="form.email"
                                    type="email"
                                    label="Email (Optional)"
                                    placeholder="e.g. dara@example.com"
                                    :error="errors.email"
                                />
                                <Input
                                    v-model="form.subject"
                                    label="Subject"
                                    placeholder="e.g. Order Inquiry or Delivery Question"
                                    :error="errors.subject"
                                    required
                                />
                            </div>

                            <Textarea
                                v-model="form.message"
                                label="Message"
                                rows="5"
                                placeholder="Please share how our concierge may assist you..."
                                :error="errors.message"
                                required
                            />

                            <div class="pt-2 flex justify-end">
                                <Button
                                    type="submit"
                                    variant="primary"
                                    :loading="isSubmitting"
                                    class="flex items-center gap-2"
                                >
                                    <Send class="w-4 h-4" />
                                    <span>Send Message</span>
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
