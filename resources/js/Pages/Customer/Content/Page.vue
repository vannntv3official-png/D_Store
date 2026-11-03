<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { useTrans } from '@/Composables/useTrans';
import { ArrowLeft, Clock, ShieldCheck } from 'lucide-vue-next';

interface Props {
    page: {
        title_en: string;
        title_km: string;
        content_en: string;
        content_km: string;
        updated_at: string;
    };
}

const props = defineProps<Props>();
const { currentLocale } = useTrans();

const title = () => currentLocale.value === 'km' ? (props.page.title_km || props.page.title_en) : props.page.title_en;
const content = () => currentLocale.value === 'km' ? (props.page.content_km || props.page.content_en) : props.page.content_en;
</script>

<template>
    <CustomerLayout>
        <Head :title="`${title()} — D STORE`" />

        <div class="max-w-4xl mx-auto px-4 sm:px-8 py-12">
            <Link href="/" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#86868B] hover:text-[#111111] mb-8 transition-colors">
                <ArrowLeft class="w-3.5 h-3.5" />
                <span>Return to Home</span>
            </Link>

            <article class="bg-white rounded-3xl p-8 sm:p-14 border border-[#E5E5EA] shadow-sm">
                <header class="mb-10 pb-8 border-b border-[#F5F5F7]">
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-[#0071E3] mb-3">
                        <ShieldCheck class="w-3.5 h-3.5" />
                        <span>Official D STORE Documentation</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-black text-[#111111] tracking-tight">
                        {{ title() }}
                    </h1>

                    <div class="flex items-center gap-2 text-xs text-[#86868B] mt-4">
                        <Clock class="w-3.5 h-3.5" />
                        <span>Last updated: {{ page.updated_at }}</span>
                    </div>
                </header>

                <div
                    class="prose prose-neutral max-w-none text-[#1D1D1F] leading-relaxed text-sm sm:text-base space-y-6 whitespace-pre-line"
                >
                    {{ content() }}
                </div>
            </article>
        </div>
    </CustomerLayout>
</template>
