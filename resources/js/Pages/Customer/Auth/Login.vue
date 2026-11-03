<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import { useTrans } from '@/Composables/useTrans';
import { Lock, ArrowRight } from 'lucide-vue-next';

const { t } = useTrans();

const form = reactive({
    login: '',
    password: '',
    remember: true,
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const submit = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post('/login', form, {
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
    <CustomerLayout>
        <Head title="Sign In — D STORE" />

        <div class="min-h-[70vh] flex items-center justify-center px-4 py-16">
            <div class="w-full max-w-md bg-white rounded-3xl p-8 sm:p-10 border border-[#E5E5EA] shadow-xl space-y-6">
                <div class="text-center space-y-2">
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">
                        {{ t('nav.login') }}
                    </h1>
                    <p class="text-xs text-[#86868B]">
                        Access your D STORE orders, wishlist, and D-Rewards points.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <Input
                        v-model="form.login"
                        label="Phone Number or Email"
                        placeholder="e.g. 012 345 678 or you@domain.com"
                        :error="errors.login"
                        required
                    />

                    <Input
                        v-model="form.password"
                        type="password"
                        label="Password"
                        placeholder="••••••••"
                        :error="errors.password"
                        required
                    />

                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-[#1D1D1F]">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded text-[#111111] focus:ring-[#111111] w-4 h-4"
                            />
                            <span>Remember me</span>
                        </label>
                    </div>

                    <Button
                        type="submit"
                        variant="primary"
                        size="lg"
                        class="w-full"
                        :loading="isSubmitting"
                    >
                        <span>{{ t('nav.login') }}</span>
                        <ArrowRight class="w-4 h-4 ml-1" />
                    </Button>
                </form>

                <div class="pt-4 border-t border-[#F5F5F7] text-center space-y-3 text-xs text-[#86868B]">
                    <div>
                        <span>Don't have an account yet? </span>
                        <Link href="/register" class="font-bold text-[#111111] hover:underline">
                            {{ t('nav.register') }}
                        </Link>
                    </div>
                    <div class="pt-2 border-t border-[#F5F5F7]">
                        <span>Store Administrator or Staff? </span>
                        <Link href="/admin/login" class="font-bold text-[#0071E3] hover:underline">
                            Staff Portal Access →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
