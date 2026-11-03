<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import { ShieldCheck, ArrowRight, Lock, Store } from 'lucide-vue-next';

const form = reactive({
    email: '',
    password: '',
    remember: true,
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const submit = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post('/admin/login', form, {
        onError: (err) => {
            errors.value = err;
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#111111] flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-white selection:bg-white selection:text-[#111111]">
        <Head title="Staff Access — D STORE" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center space-y-3">
            <div class="inline-flex items-center gap-2 p-3 rounded-2xl bg-white/10 text-white mb-2">
                <Lock class="w-6 h-6" />
            </div>
            <h2 class="text-3xl font-black tracking-tight text-white">
                D STORE Staff Portal
            </h2>
            <p class="text-xs text-[#86868B]">
                Authorized merchant administration & fulfillment control
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-[#1D1D1F] py-8 px-6 sm:px-10 rounded-3xl border border-white/10 shadow-2xl space-y-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                            Staff Email or Phone
                        </label>
                        <input
                            v-model="form.email"
                            type="text"
                            required
                            placeholder="admin@dstore.com.kh or 0969015314"
                            class="w-full px-4 py-3 bg-[#111111] text-white text-sm rounded-xl border border-white/15 focus:outline-none focus:ring-2 focus:ring-white"
                        />
                        <p v-if="errors.email" class="text-xs text-[#DC2626] mt-1">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                            Password
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            placeholder="••••••••"
                            class="w-full px-4 py-3 bg-[#111111] text-white text-sm rounded-xl border border-white/15 focus:outline-none focus:ring-2 focus:ring-white"
                        />
                        <p v-if="errors.password" class="text-xs text-[#DC2626] mt-1">{{ errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1 text-[#86868B]">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded bg-[#111111] border-white/20 text-white focus:ring-white"
                            />
                            <span>Remember this terminal</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="w-full mt-2 py-3.5 px-4 rounded-xl bg-white text-[#111111] font-bold text-sm hover:bg-[#F5F5F7] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <span>Authorize Access</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </form>

                <div class="pt-4 border-t border-white/10 flex flex-col items-center gap-3">
                    <div class="flex items-center gap-2 text-[11px] text-[#86868B]">
                        <ShieldCheck class="w-3.5 h-3.5 text-[#16A34A]" />
                        <span>Protected by RBAC & Session Anomaly Detection</span>
                    </div>
                    <Link href="/" class="inline-flex items-center gap-1.5 text-xs text-[#86868B] hover:text-white transition-colors">
                        <Store class="w-3.5 h-3.5" />
                        <span>Return to Customer Storefront</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
